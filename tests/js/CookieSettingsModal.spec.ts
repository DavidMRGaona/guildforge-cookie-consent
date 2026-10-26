import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CookieSettingsModal from '../../resources/js/components/CookieSettingsModal.vue';
import type { BannerConfig, Cookie, CookieCategory } from '../../resources/js/types/cookie-consent';

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

const config: BannerConfig = {
    position: 'bottom',
    layout: 'bar',
    theme: 'light',
    colors: { primary: '#10B981', secondary: '#6B7280', background: '#FFFFFF', text: '#1F2937' },
    showRejectAll: true,
    showSettingsButton: true,
    validityDays: 365,
    reconsentOnChange: true,
    configVersion: 1,
    texts,
    gtmEnabled: false,
    gtmContainerId: '',
    consentModeEnabled: true,
};

function cookie(overrides: Partial<Cookie>): Cookie {
    return {
        id: 'cookie-1',
        name: 'tn_guest_registrations',
        provider: 'Example',
        domain: null,
        purpose: 'Remembers the tournaments you signed up for as a guest.',
        duration: '90 días',
        type: 'first_party',
        ...overrides,
    };
}

function category(cookies: Cookie[]): CookieCategory {
    return {
        id: 'category-1',
        name: 'Necessary',
        slug: 'necessary',
        description: 'Needed for the site to work.',
        isRequired: true,
        sortOrder: 0,
        consentModeKeys: [],
        cookies,
    };
}

async function mountExpanded(cookies: Cookie[]) {
    const wrapper = mount(CookieSettingsModal, {
        props: { categories: [category(cookies)], config },
        global: { stubs: { teleport: true } },
    });

    await wrapper
        .findAll('button')
        .find((button) => button.text().startsWith(texts.showCookies))
        ?.trigger('click');

    return wrapper;
}

describe('CookieSettingsModal', () => {
    it('shows what each cookie is for under its name', async () => {
        const wrapper = await mountExpanded([
            cookie({
                id: 'gt',
                name: 'gt_guest_registrations',
                purpose: 'Remembers the game tables you joined.',
            }),
            cookie({
                id: 'tn',
                name: 'tn_guest_registrations',
                purpose: 'Remembers the tournaments you joined.',
            }),
        ]);

        const cells = wrapper.findAll('tbody tr').map((row) => row.find('td'));

        expect(cells.map((cell) => cell.find('.font-mono').text())).toEqual([
            'gt_guest_registrations',
            'tn_guest_registrations',
        ]);
        expect(cells.map((cell) => cell.find('p').text())).toEqual([
            'Remembers the game tables you joined.',
            'Remembers the tournaments you joined.',
        ]);
        // The purpose is prose, not part of the technical name
        expect(cells.every((cell) => !cell.find('p').classes().includes('font-mono'))).toBe(true);
    });

    it.each(['', '   '])('shows nothing under the name when the purpose is %j', async (purpose) => {
        const wrapper = await mountExpanded([cookie({ purpose })]);

        expect(wrapper.find('tbody td').find('p').exists()).toBe(false);
    });

    it('shows a focus ring on every button, only when moving with the keyboard', async () => {
        const buttons = (await mountExpanded([cookie({})])).findAll('button');

        expect(buttons.length).toBeGreaterThan(0);
        for (const candidate of buttons) {
            expect(candidate.classes()).toContain('focus-visible:ring-2');
            // A plain focus ring would also show after every mouse click
            expect(candidate.classes()).not.toContain('focus:ring-2');
        }
    });

    it('draws «cancel» as a button, like the banner secondary ones', async () => {
        const cancel = (await mountExpanded([cookie({})]))
            .findAll('button')
            .find((candidate) => candidate.text() === texts.cancel);

        expect(cancel?.classes()).toEqual(
            expect.arrayContaining(['bg-muted', 'border', 'border-base-muted'])
        );
    });

    it('takes every label from the configured texts', async () => {
        const wrapper = await mountExpanded([cookie({})]);

        expect(wrapper.text()).toContain('Always on');
        expect(wrapper.text()).toContain('Show the cookies (1)');
        expect(wrapper.findAll('thead th').map((th) => th.text())).toEqual([
            'Name',
            'Set by',
            'Lifetime',
        ]);
        expect(wrapper.findAll('button').some((button) => button.text() === 'Discard')).toBe(true);
        expect(wrapper.find('button[aria-label="Close the settings"]').exists()).toBe(true);

        for (const hardcoded of [
            'Obligatoria',
            'Ver cookies',
            'Proveedor',
            'Duración',
            'Cancelar',
        ]) {
            expect(wrapper.text()).not.toContain(hardcoded);
        }
    });
});
