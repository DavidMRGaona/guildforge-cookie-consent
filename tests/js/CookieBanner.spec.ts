import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount, type DOMWrapper, type VueWrapper } from '@vue/test-utils';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import type { BannerConfig, CookieCategory } from '../../resources/js/types/cookie-consent';

const texts: BannerConfig['texts'] = {
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
};

function config(overrides: Partial<BannerConfig> = {}): BannerConfig {
    return {
        position: 'bottom',
        layout: 'bar',
        theme: 'light',
        colors: {
            primary: '#10B981',
            secondary: '#6B7280',
            background: '#FFFFFF',
            text: '#1F2937',
        },
        showRejectAll: true,
        showSettingsButton: true,
        validityDays: 365,
        reconsentOnChange: true,
        configVersion: 1,
        texts,
        gtmEnabled: false,
        gtmContainerId: '',
        gtmCategory: 'analytics',
        consentModeEnabled: true,
        ...overrides,
    };
}

function mountBanner(bannerConfig: BannerConfig): VueWrapper {
    return mount(CookieBanner, {
        props: { cookieConsent: { categories: [], config: bannerConfig } },
        global: { stubs: { teleport: true } },
    });
}

function button(wrapper: VueWrapper, label: string): DOMWrapper<HTMLButtonElement> {
    const found = wrapper.findAll('button').find((candidate) => candidate.text() === label);
    if (!found) throw new Error(`No button labelled "${label}"`);

    return found;
}

describe('CookieBanner', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it.each([texts.settings, texts.rejectAll])(
        'draws "%s" as a button, with a visible background and border',
        (label) => {
            const classes = button(mountBanner(config()), label).classes();

            expect(classes).toEqual(
                expect.arrayContaining(['bg-muted', 'border', 'border-base-muted'])
            );
            // A 10 % grey on the white banner could not be told apart from it
            expect(classes).not.toContain('bg-base-secondary/10');
        }
    );

    it.each(['light', 'custom'] as const)(
        'shows a focus ring on every button with the %s theme',
        (theme) => {
            const buttons = mountBanner(config({ theme })).findAll('button');

            expect(buttons.map((candidate) => candidate.text())).toEqual([
                texts.settings,
                texts.rejectAll,
                texts.acceptAll,
            ]);
            for (const candidate of buttons) {
                expect(candidate.classes()).toEqual(
                    expect.arrayContaining(['focus-visible:ring-2', 'focus-visible:ring-offset-2'])
                );
            }
        }
    );

    it('keeps the panel colours with the custom theme', () => {
        const wrapper = mountBanner(config({ theme: 'custom' }));

        for (const label of [texts.settings, texts.rejectAll]) {
            const secondary = button(wrapper, label);
            expect(secondary.attributes('style')).toContain('background-color: #6B7280');
            expect(secondary.classes()).not.toContain('bg-muted');
        }
        expect(button(wrapper, texts.acceptAll).attributes('style')).toContain(
            'background-color: #10B981'
        );
    });
});

describe('CookieBanner themes', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    function surface(theme: BannerConfig['theme']): string {
        const wrapper = mountBanner(config({ theme }));

        return (wrapper.find('div.shadow-lg').element as HTMLElement).style.getPropertyValue(
            '--color-bg-surface'
        );
    }

    it('paints the light and dark themes differently', () => {
        expect(surface('light')).toBe('#ffffff');
        expect(surface('dark')).toBe('#302d2a');
    });

    it('sets no theme colours with the custom theme', () => {
        expect(surface('custom')).toBe('');
    });
});

describe('CookieBanner and Google Tag Manager', () => {
    const categories: CookieCategory[] = [
        category('necessary', true),
        category('analytics', false),
        category('marketing', false),
    ];

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

    function containerScripts(): Element[] {
        return Array.from(document.querySelectorAll('script[src*="googletagmanager.com/gtm.js"]'));
    }

    async function choose(bannerConfig: BannerConfig, label: string): Promise<void> {
        const wrapper = mount(CookieBanner, {
            props: { cookieConsent: { categories, config: bannerConfig } },
            global: { stubs: { teleport: true } },
        });
        await button(wrapper, label).trigger('click');
        await flushPromises();
    }

    const withoutConsentMode = (overrides: Partial<BannerConfig> = {}): BannerConfig =>
        config({
            gtmEnabled: true,
            gtmContainerId: 'GTM-ABC123',
            gtmCategory: 'analytics',
            consentModeEnabled: false,
            ...overrides,
        });

    beforeEach(() => {
        localStorage.clear();
        document.head.innerHTML = '';
        delete window.dataLayer;
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('loads the container as soon as its category is accepted, without reloading', async () => {
        await choose(withoutConsentMode(), texts.acceptAll);

        expect(containerScripts()).toHaveLength(1);
    });

    it('does not load the container when its category is rejected', async () => {
        await choose(withoutConsentMode(), texts.rejectAll);

        expect(containerScripts()).toHaveLength(0);
    });

    it('waits for the category chosen for the container', async () => {
        await choose(withoutConsentMode({ gtmCategory: 'missing' }), texts.acceptAll);

        expect(containerScripts()).toHaveLength(0);
    });

    it('leaves the container to the server with Consent Mode', async () => {
        await choose(withoutConsentMode({ consentModeEnabled: true }), texts.acceptAll);

        expect(containerScripts()).toHaveLength(0);
    });
});
