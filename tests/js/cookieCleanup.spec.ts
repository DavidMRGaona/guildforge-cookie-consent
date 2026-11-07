import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { VNode } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import type { Cookie, CookieCategory } from '../../resources/js/types/cookie-consent';
import { cookieDomains, deleteCookiesOfRejected } from '../../resources/js/utils/cookie-cleanup';
import { bannerConfig, category, storeConsent, texts } from './support/fixtures';

vi.mock('../../resources/js/utils/page', () => ({ reloadPage: vi.fn() }));

vi.mock('@/components/knowledge/HelpLink.vue', async () => {
    const { defineComponent, h } = await import('vue');

    return {
        default: defineComponent({
            name: 'HelpLink',
            setup() {
                return (): VNode => h('a', '?');
            },
        }),
    };
});

function declared(name: string): Cookie {
    return {
        id: `cookie-${name}`,
        name,
        provider: 'Example',
        domain: null,
        purpose: '',
        duration: null,
        type: 'first_party',
    };
}

function cookieNames(): string[] {
    return document.cookie
        .split(';')
        .map((pair) => pair.split('=')[0]?.trim() ?? '')
        .filter((name) => name !== '')
        .sort();
}

function setCookie(name: string): void {
    document.cookie = `${name}=1; path=/`;
}

const categories: CookieCategory[] = [
    category('necessary', { isRequired: true, cookies: [declared('session_hint')] }),
    category('preferences', { cookies: [declared('gt_guest_registrations')] }),
    category('analytics', { cookies: [declared('_ga'), declared('_ga_*')] }),
];

beforeEach(() => {
    for (const name of cookieNames()) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    }
    localStorage.clear();
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('deleteCookiesOfRejected', () => {
    it('deletes the declared cookies of every category not accepted', () => {
        ['session_hint', 'gt_guest_registrations', '_ga', 'unrelated'].forEach(setCookie);

        deleteCookiesOfRejected(categories, {
            necessary: true,
            preferences: false,
            analytics: false,
        });

        expect(cookieNames()).toEqual(['session_hint', 'unrelated']);
    });

    it('keeps the cookies of the accepted categories', () => {
        ['gt_guest_registrations', '_ga'].forEach(setCookie);

        deleteCookiesOfRejected(categories, {
            necessary: true,
            preferences: true,
            analytics: false,
        });

        expect(cookieNames()).toEqual(['gt_guest_registrations']);
    });

    it('never deletes the cookies of a required category', () => {
        setCookie('session_hint');

        deleteCookiesOfRejected(categories, {
            necessary: false,
            preferences: false,
            analytics: false,
        });

        expect(cookieNames()).toEqual(['session_hint']);
    });

    it('takes a name ending in * as every cookie starting that way', () => {
        ['_ga_ABC123', '_ga_XYZ', '_gat'].forEach(setCookie);

        deleteCookiesOfRejected(categories, {
            necessary: true,
            preferences: true,
            analytics: false,
        });

        expect(cookieNames()).toEqual(['_gat']);
    });

    it('never deletes the consent cookie, nor everything with a bare *', () => {
        ['guildforge_cookie_consent', 'anything'].forEach(setCookie);
        const careless = [
            category('analytics', {
                cookies: [declared('*'), declared('guildforge_cookie_consent'), declared('guild*')],
            }),
        ];

        deleteCookiesOfRejected(careless, { analytics: false });

        expect(cookieNames()).toEqual(['anything', 'guildforge_cookie_consent']);
    });

    it('ignores names that are not cookie names', () => {
        const spy = vi.spyOn(document, 'cookie', 'set');

        deleteCookiesOfRejected(
            [category('analytics', { cookies: [declared('a; domain=example.org')] })],
            {
                analytics: false,
            }
        );

        expect(spy).not.toHaveBeenCalled();
        spy.mockRestore();
    });
});

describe('cookieDomains', () => {
    it('lists the site and each parent domain, without the top level one', () => {
        expect(cookieDomains('www.club.example.org')).toEqual([
            'www.club.example.org',
            'club.example.org',
            'example.org',
        ]);
    });

    it.each(['localhost', '127.0.0.1', '[::1]'])('lists nothing for %s', (hostname) => {
        expect(cookieDomains(hostname)).toEqual([]);
    });
});

describe('the banner', () => {
    let wrapper: VueWrapper | null = null;

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
    });

    it('deletes the cookies of a category once consent to it is withdrawn', async () => {
        storeConsent({ necessary: true, preferences: true, analytics: false });
        setCookie('gt_guest_registrations');
        wrapper = mount(CookieBanner, {
            props: { cookieConsent: { categories, config: bannerConfig() } },
            global: { stubs: { teleport: true } },
        });
        await wrapper.find(`button[aria-label="${texts.preferences}"]`).trigger('click');

        wrapper.findComponent(CookieSettingsModal).vm.$emit('save', {
            necessary: true,
            preferences: false,
            analytics: false,
        });
        await flushPromises();

        expect(cookieNames()).not.toContain('gt_guest_registrations');
    });

    it('deletes nothing when the choice was not saved', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 500 })));
        setCookie('gt_guest_registrations');
        wrapper = mount(CookieBanner, {
            props: { cookieConsent: { categories, config: bannerConfig() } },
            global: { stubs: { teleport: true } },
        });

        await wrapper
            .findAll('button')
            .find((button) => button.text() === texts.rejectAll)
            ?.trigger('click');
        await flushPromises();

        expect(cookieNames()).toContain('gt_guest_registrations');
    });
});
