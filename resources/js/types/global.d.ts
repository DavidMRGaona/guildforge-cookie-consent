export interface GuildForgeCookieConsentApi {
    /**
     * Whether the visitor consented to the cookie category.
     * Required categories always return true.
     */
    hasConsent(categorySlug: string): boolean;
}

declare global {
    // Published by the cookie banner; absent when cookie-consent is not on the page
    var GuildForgeCookieConsent: GuildForgeCookieConsentApi | undefined;
}
