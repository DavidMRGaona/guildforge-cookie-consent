import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, type VNode } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { hasCookieConsent } from '@/contracts/cookieConsent';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import { bannerConfig, category } from './support/fixtures';

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

const categories = [category('necessary', { isRequired: true }), category('analytics')];

let wrapper: VueWrapper | null = null;

function mountBanner(): VueWrapper {
    wrapper = mount(CookieBanner, {
        props: { cookieConsent: { categories, config } },
        global: { stubs: { teleport: true } },
    });

    return wrapper;
}

async function click(banner: VueWrapper, label: string): Promise<void> {
    await banner
        .findAll('button')
        .find((button) => button.text() === label)
        ?.trigger('click');
    await flushPromises();
}

describe('the banner as the cookie consent provider', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
        vi.unstubAllGlobals();
    });

    it('answers for the categories: required ones always count as consented', () => {
        mountBanner();

        expect(hasCookieConsent('necessary')).toBe(true);
        expect(hasCookieConsent('analytics')).toBe(false);
    });

    // An unused category is not offered, so an older choice about it no longer counts
    it('does not count a category the banner no longer offers as consented', () => {
        localStorage.setItem(
            'guildforge_cookie_consent',
            JSON.stringify({
                visitorId: 'visitor',
                version: config.configVersion,
                timestamp: new Date().toISOString(),
                preferences: { necessary: true, analytics: true, marketing: true },
            })
        );

        mountBanner();

        expect(hasCookieConsent('analytics')).toBe(true);
        expect(hasCookieConsent('marketing')).toBe(false);
    });

    // Modules read consent inside computed values: each choice must reach them
    it('lets readers follow the choice made with "Accept all"', async () => {
        const analytics = computed(() => hasCookieConsent('analytics'));
        const banner = mountBanner();
        expect(analytics.value).toBe(false);

        await click(banner, config.texts.acceptAll);

        expect(analytics.value).toBe(true);
    });

    it('lets readers follow the choice made with "Reject all"', async () => {
        // Rejecting changes no answer here, so count how often readers ask again
        let reads = 0;
        const analytics = computed(() => {
            reads += 1;

            return hasCookieConsent('analytics');
        });
        const banner = mountBanner();
        expect(analytics.value).toBe(false);
        const readsBefore = reads;

        await click(banner, config.texts.rejectAll);

        expect(analytics.value).toBe(false);
        expect(reads).toBe(readsBefore + 1);
    });

    it('lets readers follow the choice saved in the preferences', async () => {
        const analytics = computed(() => hasCookieConsent('analytics'));
        const banner = mountBanner();
        expect(analytics.value).toBe(false);
        await click(banner, config.texts.settings);

        banner
            .findComponent(CookieSettingsModal)
            .vm.$emit('save', { necessary: true, analytics: true });
        await flushPromises();

        expect(analytics.value).toBe(true);
    });
});
