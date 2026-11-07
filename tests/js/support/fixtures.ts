import type { BannerConfig, CookieCategory } from '../../../resources/js/types/cookie-consent';

export const texts: BannerConfig['texts'] = {
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
    preferences: 'Cookie preferences',
    saveError: 'Your choice was not saved.',
};

export function bannerConfig(overrides: Partial<BannerConfig> = {}): BannerConfig {
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
        showFloatingButton: true,
        validityDays: 365,
        reconsentOnChange: true,
        configVersion: 1,
        preferencesUrl: '/cookie-preferences',
        texts,
        gtmEnabled: false,
        gtmContainerId: '',
        gtmCategory: 'analytics',
        consentModeEnabled: true,
        consentModeDefaults: {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'granted',
        },
        ...overrides,
    };
}

export function category(slug: string, overrides: Partial<CookieCategory> = {}): CookieCategory {
    return {
        id: `category-${slug}`,
        name: slug,
        slug,
        description: '',
        isRequired: false,
        sortOrder: 0,
        consentModeKeys: [],
        hasScripts: false,
        cookies: [],
        ...overrides,
    };
}

/** What the banner stores once the visitor chooses (consent-manager.ts). */
export function storeConsent(preferences: Record<string, boolean>, version = 1): void {
    localStorage.setItem(
        'guildforge_cookie_consent',
        JSON.stringify({
            visitorId: '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
            version,
            timestamp: new Date().toISOString(),
            preferences,
        })
    );
}
