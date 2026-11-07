import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { VNode } from 'vue';
import { flushPromises, mount, type DOMWrapper, type VueWrapper } from '@vue/test-utils';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import type { BannerConfig, CookieCategory } from '../../resources/js/types/cookie-consent';
import { reloadPage } from '../../resources/js/utils/page';
import { bannerConfig, category, storeConsent, texts } from './support/fixtures';

vi.mock('../../resources/js/utils/page', () => ({ reloadPage: vi.fn() }));

// The help button asks the server which guides the person may read
vi.mock('@/components/knowledge/HelpLink.vue', async () => {
    const { defineComponent, h } = await import('vue');

    return {
        default: defineComponent({
            name: 'HelpLink',
            props: { guide: { type: String, required: true } },
            setup() {
                return (): VNode => h('a', '?');
            },
        }),
    };
});

const categories: CookieCategory[] = [
    category('necessary', { isRequired: true }),
    category('analytics', { hasScripts: true }),
    category('preferences'),
];

let wrapper: VueWrapper | null = null;

function mountBanner(
    config: BannerConfig = bannerConfig(),
    withCategories = categories
): VueWrapper {
    wrapper = mount(CookieBanner, {
        props: { cookieConsent: { categories: withCategories, config } },
        global: { stubs: { teleport: true } },
        attachTo: document.body,
    });

    return wrapper;
}

function floatingButton(banner: VueWrapper): DOMWrapper<Element> {
    return banner.find(`button[aria-label="${texts.preferences}"]`);
}

async function saveInPreferences(
    banner: VueWrapper,
    preferences: Record<string, boolean>
): Promise<void> {
    banner.findComponent(CookieSettingsModal).vm.$emit('save', preferences);
    await flushPromises();
}

describe('reopening the cookie preferences', () => {
    beforeEach(() => {
        localStorage.clear();
        document.head.innerHTML = '';
        vi.mocked(reloadPage).mockClear();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
        window.history.replaceState(null, '', '/');
        vi.unstubAllGlobals();
    });

    it('offers the floating button once the visitor has chosen', async () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });
        const banner = mountBanner();

        await floatingButton(banner).trigger('click');

        expect(banner.findComponent(CookieSettingsModal).exists()).toBe(true);
    });

    it('hides the floating button while the notice is shown', () => {
        expect(floatingButton(mountBanner()).exists()).toBe(false);
    });

    it('hides the floating button when the site turns it off', () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });

        expect(
            floatingButton(mountBanner(bannerConfig({ showFloatingButton: false }))).exists()
        ).toBe(false);
    });

    it.each(['auto', 'light', 'dark'] as const)(
        'paints the floating button with the site colours, not the fixed %s theme ones',
        (theme) => {
            storeConsent({ necessary: true, analytics: false, preferences: false });
            const button = floatingButton(mountBanner(bannerConfig({ theme })));

            expect(
                (button.element as HTMLElement).style.getPropertyValue('--color-bg-surface')
            ).toBe('');
            expect(button.classes()).toEqual(
                expect.arrayContaining([
                    'bg-elevated',
                    'text-base-primary',
                    'border',
                    'border-base-muted',
                ])
            );
            // border-strong is a decorative hairline (1.4:1 on the light page, 2.1:1 on the dark
            // one): the button's outline must reach the 3:1 WCAG 1.4.11 asks for
            expect(button.classes()).not.toContain('border-strong');
        }
    );

    it('keeps the panel colours on the floating button with the custom theme', () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });
        const button = floatingButton(mountBanner(bannerConfig({ theme: 'custom' })));

        expect((button.element as HTMLElement).style.backgroundColor).not.toBe('');
        expect(button.classes()).not.toContain('bg-elevated');
    });

    it('opens the preferences from a link to them anywhere on the page, without leaving it', async () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });
        const banner = mountBanner();
        const link = document.createElement('a');
        link.href = `${window.location.origin}/cookie-preferences`;
        document.body.appendChild(link);
        const click = new MouseEvent('click', { bubbles: true, cancelable: true });

        link.dispatchEvent(click);
        await flushPromises();

        expect(click.defaultPrevented).toBe(true);
        expect(banner.findComponent(CookieSettingsModal).exists()).toBe(true);
        link.remove();
    });

    it('leaves other links alone', async () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });
        const banner = mountBanner();
        const link = document.createElement('a');
        link.href = `${window.location.origin}/events`;
        link.addEventListener('click', (event) => event.preventDefault());
        document.body.appendChild(link);

        link.click();
        await flushPromises();

        expect(banner.findComponent(CookieSettingsModal).exists()).toBe(false);
        link.remove();
    });

    it('opens the preferences when the page is reached through the link, and forgets the anchor', async () => {
        storeConsent({ necessary: true, analytics: false, preferences: false });
        window.history.replaceState(null, '', '/#preferencias-cookies');

        const banner = mountBanner();
        await flushPromises();

        expect(banner.findComponent(CookieSettingsModal).exists()).toBe(true);
        expect(window.location.hash).toBe('');
    });
});

describe('applying a new choice to the page', () => {
    beforeEach(() => {
        localStorage.clear();
        document.head.innerHTML = '';
        vi.mocked(reloadPage).mockClear();
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
        vi.unstubAllGlobals();
    });

    it('reloads the page when consent is withdrawn from a category with scripts', async () => {
        storeConsent({ necessary: true, analytics: true, preferences: false });
        const banner = mountBanner();
        await floatingButton(banner).trigger('click');

        await saveInPreferences(banner, { necessary: true, analytics: false, preferences: false });

        expect(reloadPage).toHaveBeenCalledOnce();
    });

    it('reloads the page when a category with scripts is accepted, so they load', async () => {
        const banner = mountBanner();

        await banner
            .findAll('button')
            .find((button) => button.text() === texts.acceptAll)
            ?.trigger('click');
        await flushPromises();

        expect(reloadPage).toHaveBeenCalledOnce();
    });

    it('does not reload when only categories without scripts change', async () => {
        storeConsent({ necessary: true, analytics: true, preferences: false });
        const banner = mountBanner();
        await floatingButton(banner).trigger('click');

        await saveInPreferences(banner, { necessary: true, analytics: true, preferences: true });

        expect(reloadPage).not.toHaveBeenCalled();
    });

    it('does not reload when the first choice rejects everything', async () => {
        const banner = mountBanner();

        await banner
            .findAll('button')
            .find((button) => button.text() === texts.rejectAll)
            ?.trigger('click');
        await flushPromises();

        expect(reloadPage).not.toHaveBeenCalled();
    });

    it('reloads when Google Tag Manager loses its category without Consent Mode', async () => {
        const config = bannerConfig({
            gtmEnabled: true,
            gtmContainerId: 'GTM-ABC123',
            gtmCategory: 'preferences',
            consentModeEnabled: false,
        });
        const banner = mountBanner(config);

        // Accepting it loads the container right away, without reloading
        await banner
            .findAll('button')
            .find((button) => button.text() === texts.settings)
            ?.trigger('click');
        await saveInPreferences(banner, { necessary: true, analytics: false, preferences: true });
        expect(reloadPage).not.toHaveBeenCalled();

        await floatingButton(banner).trigger('click');
        await saveInPreferences(banner, { necessary: true, analytics: false, preferences: false });

        expect(reloadPage).toHaveBeenCalledOnce();
    });
});
