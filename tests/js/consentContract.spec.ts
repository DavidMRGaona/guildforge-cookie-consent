import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { hasCookieConsent } from '@/contracts/cookieConsent';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import type { BannerConfig, CookieCategory } from '../../resources/js/types/cookie-consent';

vi.mock('@/components/knowledge/HelpLink.vue', async () => {
    const { defineComponent, h } = await import('vue');

    return { default: defineComponent({ name: 'HelpLink', setup: () => () => h('a', '?') }) };
});

const config = {
    position: 'bottom',
    layout: 'bar',
    theme: 'light',
    colors: { primary: '#10B981', secondary: '#6B7280', background: '#FFFFFF', text: '#1F2937' },
    showRejectAll: true,
    showSettingsButton: true,
    validityDays: 365,
    reconsentOnChange: true,
    configVersion: 1,
    texts: {
        title: 'Cookie notice',
        description: 'We use cookies.',
        acceptAll: 'Accept all',
        rejectAll: 'Reject all',
        settings: 'Settings',
        save: 'Save',
        privacyLink: 'Privacy policy',
        privacyUrl: '/privacy',
        required: 'Always on',
        showCookies: 'Show the cookies',
        cookieColumn: 'Name',
        providerColumn: 'Set by',
        durationColumn: 'Lifetime',
        cancel: 'Discard',
        close: 'Close the settings',
    },
    gtmEnabled: false,
    gtmContainerId: '',
    gtmCategory: 'analytics',
    consentModeEnabled: true,
} satisfies BannerConfig;

function category(slug: string, isRequired: boolean): CookieCategory {
    return {
        id: `category-${slug}`,
        name: slug,
        slug,
        description: '',
        isRequired,
        sortOrder: 0,
        consentModeKeys: [],
        cookies: [],
    };
}

const categories = [category('necessary', true), category('analytics', false)];

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
