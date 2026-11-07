import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { VNode } from 'vue';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import CookieBanner from '../../resources/js/components/CookieBanner.vue';
import type { CookieCategory } from '../../resources/js/types/cookie-consent';
import { consentModeUpdate } from '../../resources/js/utils/consent-mode';
import fixture from '../fixtures/consent-mode-cases.json';
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

// The same cases as ConsentModeServiceTest on the server
const categories: CookieCategory[] = fixture.categories.map((entry) =>
    category(entry.slug, { isRequired: entry.isRequired, consentModeKeys: entry.consentModeKeys })
);

describe('consentModeUpdate', () => {
    it.each(fixture.cases)('maps $name like the server', ({ preferences, expected }) => {
        expect(
            consentModeUpdate(
                categories,
                preferences as Record<string, boolean>,
                fixture.defaults as Record<string, 'granted' | 'denied'>
            )
        ).toEqual(expected);
    });
});

describe('the banner and Consent Mode', () => {
    const gtag = vi.fn();
    let wrapper: VueWrapper | null = null;

    function mountBanner(consentModeEnabled = true): VueWrapper {
        wrapper = mount(CookieBanner, {
            props: {
                cookieConsent: {
                    categories,
                    config: bannerConfig({
                        consentModeEnabled,
                        consentModeDefaults: fixture.defaults as Record<
                            string,
                            'granted' | 'denied'
                        >,
                    }),
                },
            },
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

    beforeEach(() => {
        localStorage.clear();
        gtag.mockClear();
        window.gtag = gtag;
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 201 })));
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
        delete window.gtag;
        vi.unstubAllGlobals();
    });

    it('grants the keys of the accepted categories, whatever their identifiers', async () => {
        await click(mountBanner(), texts.acceptAll);

        expect(gtag).toHaveBeenCalledWith('consent', 'update', fixture.cases[2]?.expected);
    });

    it('denies again what was withdrawn', async () => {
        storeConsent({
            imprescindibles: true,
            estadisticas: true,
            publicidad: true,
            'sin-claves': true,
        });
        const banner = mountBanner();
        await banner.find(`button[aria-label="${texts.preferences}"]`).trigger('click');

        await click(banner, texts.rejectAll);
        await click(banner, texts.save);

        expect(gtag).toHaveBeenLastCalledWith('consent', 'update', fixture.cases[0]?.expected);
    });

    it('sends nothing to Google without Consent Mode', async () => {
        await click(mountBanner(false), texts.acceptAll);

        expect(gtag).not.toHaveBeenCalled();
    });

    it('sends nothing when the choice was not saved', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('{}', { status: 422 })));

        await click(mountBanner(), texts.acceptAll);

        expect(gtag).not.toHaveBeenCalled();
    });
});
