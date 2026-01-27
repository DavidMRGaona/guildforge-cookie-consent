export interface CookieCategory {
    id: string;
    name: string;
    slug: string;
    description: string;
    isRequired: boolean;
    sortOrder: number;
    consentModeKeys: string[];
    cookies: Cookie[];
}

export interface Cookie {
    id: string;
    name: string;
    provider: string;
    domain: string | null;
    purpose: string;
    duration: string | null;
    type: 'first_party' | 'third_party';
}

export interface BannerConfig {
    position: 'bottom' | 'top' | 'bottom_left' | 'bottom_right' | 'center';
    layout: 'bar' | 'box' | 'modal';
    theme: 'light' | 'dark' | 'custom';
    colors: {
        primary: string;
        secondary: string;
        background: string;
        text: string;
    };
    showRejectAll: boolean;
    showSettingsButton: boolean;
    showLogo: boolean;
    blockPageUntilConsent: boolean;
    validityDays: number;
    reconsentOnChange: boolean;
    configVersion: number;
    texts: {
        title: string;
        description: string;
        acceptAll: string;
        rejectAll: string;
        settings: string;
        save: string;
        privacyLink: string;
        privacyUrl: string;
    };
    gtmEnabled: boolean;
    gtmContainerId: string;
    consentModeEnabled: boolean;
}

export interface StoredConsent {
    visitorId: string;
    version: number;
    timestamp: string;
    preferences: Record<string, boolean>;
}

export interface CookieConsentData {
    categories: CookieCategory[];
    config: BannerConfig;
}
