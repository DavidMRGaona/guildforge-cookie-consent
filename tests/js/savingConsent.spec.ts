import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, type VNode } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { hasCookieConsent } from '@/contracts/cookieConsent';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import type { CookieCategory } from '../../resources/js/types/cookie-consent';
import { getStoredConsent, saveConsent } from '../../resources/js/utils/consent-manager';
import { reloadPage } from '../../resources/js/utils/page';
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

const config = bannerConfig();

const fetchMock = vi.fn();

function answer(status: number): void {
    fetchMock.mockResolvedValue(new Response('{}', { status }));
}

function sentPreferences(): Record<string, boolean> {
    const body = fetchMock.mock.calls.at(-1)?.[1]?.body;

    return (JSON.parse(String(body)) as { preferences: Record<string, boolean> }).preferences;
}

let wrapper: VueWrapper | null = null;

function mountModal(categories: CookieCategory[]): VueWrapper {
    wrapper = mount(CookieSettingsModal, {
        props: { categories, config },
        global: { stubs: { teleport: true } },
    });

    return wrapper;
}

function mountBanner(categories: CookieCategory[]): VueWrapper {
    wrapper = mount(CookieBanner, {
        props: { cookieConsent: { categories, config } },
        global: { stubs: { teleport: true } },
    });

    return wrapper;
}

async function click(found: VueWrapper, label: string): Promise<void> {
    await found
        .findAll('button')
        .find((button) => button.text() === label)
        ?.trigger('click');
    await flushPromises();
}

function savedPreferences(modal: VueWrapper): Record<string, boolean> | undefined {
    return modal.emitted<[Record<string, boolean>]>('save')?.at(-1)?.[0];
}

beforeEach(() => {
    localStorage.clear();
    document.cookie = 'guildforge_cookie_consent=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
    fetchMock.mockReset();
    answer(201);
    vi.stubGlobal('fetch', fetchMock);
    vi.mocked(reloadPage).mockClear();
});

afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
    vi.unstubAllGlobals();
});

describe('the preferences window', () => {
    it('always includes the required categories, also those created after the choice', async () => {
        storeConsent({ necessary: true, analytics: true });
        const modal = mountModal([
            category('necessary', { isRequired: true }),
            category('security', { isRequired: true }),
            category('analytics'),
        ]);

        await click(modal, texts.save);

        expect(savedPreferences(modal)).toEqual({
            necessary: true,
            security: true,
            analytics: true,
        });
    });

    it('leaves out categories that no longer exist and starts new optional ones off', async () => {
        storeConsent({ necessary: true, removed: true });
        const modal = mountModal([
            category('necessary', { isRequired: true }),
            category('marketing'),
        ]);

        await click(modal, texts.save);

        expect(savedPreferences(modal)).toEqual({ necessary: true, marketing: false });
    });

    it('does not start from a choice that no longer counts', async () => {
        // Asked again after a configuration change: nothing may be ticked beforehand
        storeConsent({ necessary: true, analytics: true }, 0);
        const modal = mountModal([
            category('necessary', { isRequired: true }),
            category('analytics'),
        ]);

        await click(modal, texts.save);

        expect(savedPreferences(modal)).toEqual({ necessary: true, analytics: false });
    });
});

describe('saving the choice', () => {
    it.each([422, 419, 500])('stores nothing when the server answers %i', async (status) => {
        answer(status);

        await expect(saveConsent({ necessary: true, analytics: true }, 1)).rejects.toThrow();

        expect(getStoredConsent()).toBeNull();
        expect(document.cookie).not.toContain('guildforge_cookie_consent=');
    });

    it('stores nothing when the server cannot be reached', async () => {
        fetchMock.mockRejectedValue(new TypeError('Failed to fetch'));

        await expect(saveConsent({ necessary: true }, 1)).rejects.toThrow();

        expect(getStoredConsent()).toBeNull();
    });

    it('stores the choice once the server has it', async () => {
        await saveConsent({ necessary: true, analytics: true }, 1);

        expect(getStoredConsent()?.preferences).toEqual({ necessary: true, analytics: true });
        expect(document.cookie).toContain('guildforge_cookie_consent=');
    });

    it('keeps the notice and tells the visitor when the choice is not saved', async () => {
        answer(422);
        const analytics = computed(() => hasCookieConsent('analytics'));
        const banner = mountBanner([
            category('necessary', { isRequired: true }),
            category('analytics'),
        ]);

        await click(banner, texts.acceptAll);

        expect(banner.text()).toContain(texts.title);
        expect(banner.find('[role="alert"]').text()).toBe(texts.saveError);
        expect(analytics.value).toBe(false);
        expect(reloadPage).not.toHaveBeenCalled();
    });

    it('applies the choice when trying again works', async () => {
        answer(500);
        const banner = mountBanner([
            category('necessary', { isRequired: true }),
            category('analytics'),
        ]);
        await click(banner, texts.acceptAll);

        answer(201);
        await click(banner, texts.acceptAll);

        expect(banner.find('[role="alert"]').exists()).toBe(false);
        expect(getStoredConsent()?.preferences).toEqual({ necessary: true, analytics: true });
    });

    it('keeps the preferences open and tells the visitor when they are not saved', async () => {
        storeConsent({ necessary: true, analytics: false });
        const banner = mountBanner([
            category('necessary', { isRequired: true }),
            category('analytics'),
        ]);
        await banner.find(`button[aria-label="${texts.preferences}"]`).trigger('click');
        answer(422);

        const modal = banner.findComponent(CookieSettingsModal);
        await click(modal, texts.save);

        expect(banner.findComponent(CookieSettingsModal).exists()).toBe(true);
        expect(banner.findComponent(CookieSettingsModal).find('[role="alert"]').text()).toBe(
            texts.saveError
        );
        expect(getStoredConsent()?.preferences).toEqual({ necessary: true, analytics: false });
        expect(sentPreferences()).toEqual({ necessary: true, analytics: false });
    });
});
