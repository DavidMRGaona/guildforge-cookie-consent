import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, type VNode } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import {
    hasCookieConsent,
    hasCookieConsentDecided,
    onCookieConsentDecided,
} from '@/contracts/cookieConsent';
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

    describe('the banner answering whoever waits for it', () => {
        function countDecisions(): { count: () => number; stop: () => void } {
            let decisions = 0;
            const stop = onCookieConsentDecided(() => {
                decisions += 1;
            });

            return { count: () => decisions, stop };
        }

        it('is waiting for an answer while it shows, and decided once it is answered', async () => {
            const banner = mountBanner();
            expect(hasCookieConsentDecided()).toBe(false);

            await click(banner, config.texts.acceptAll);

            expect(hasCookieConsentDecided()).toBe(true);
        });

        it('counts as decided when it does not show because a choice is stored', () => {
            localStorage.setItem(
                'guildforge_cookie_consent',
                JSON.stringify({
                    visitorId: 'visitor',
                    version: config.configVersion,
                    timestamp: new Date().toISOString(),
                    preferences: { necessary: true, analytics: false },
                })
            );

            mountBanner();

            expect(hasCookieConsentDecided()).toBe(true);
        });

        // An active module without a banner must not hold back the pop-ups
        it('counts as decided when its configuration did not arrive', () => {
            wrapper = mount(CookieBanner, {
                props: { cookieConsent: { categories, config: undefined as never } },
                global: { stubs: { teleport: true } },
            });

            expect(hasCookieConsentDecided()).toBe(true);
        });

        it.each([
            ['Accept all', (): string => config.texts.acceptAll],
            ['Reject all', (): string => config.texts.rejectAll],
        ])('emits the decision once with "%s"', async (_name, label) => {
            const decisions = countDecisions();
            const banner = mountBanner();

            await click(banner, label());

            expect(decisions.count()).toBe(1);
            decisions.stop();
        });

        it('emits the decision once when the preferences are saved', async () => {
            const decisions = countDecisions();
            const banner = mountBanner();
            await click(banner, config.texts.settings);

            banner
                .findComponent(CookieSettingsModal)
                .vm.$emit('save', { necessary: true, analytics: true });
            await flushPromises();

            expect(decisions.count()).toBe(1);
            decisions.stop();
        });

        it('does not emit a decision when the choice could not be saved', async () => {
            vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('offline')));
            const decisions = countDecisions();
            const banner = mountBanner();

            await click(banner, config.texts.acceptAll);

            expect(decisions.count()).toBe(0);
            expect(hasCookieConsentDecided()).toBe(false);
            decisions.stop();
        });

        it('does not emit a decision on a page visit, answered or not', async () => {
            const decisions = countDecisions();
            const banner = mountBanner();

            // A visit with the banner still waiting, and a new configuration arriving
            await banner.setProps({ cookieConsent: { categories, config: { ...config } } });
            await flushPromises();
            expect(decisions.count()).toBe(0);

            await click(banner, config.texts.acceptAll);
            expect(decisions.count()).toBe(1);

            // Navigating after the answer: the same banner gets new props, nothing is decided again
            await banner.setProps({
                cookieConsent: { categories: [...categories], config: { ...config } },
            });
            await flushPromises();
            expect(decisions.count()).toBe(1);

            // A new page mounts the banner again with the stored choice
            wrapper?.unmount();
            mountBanner();
            expect(decisions.count()).toBe(1);
            decisions.stop();
        });
    });
});
