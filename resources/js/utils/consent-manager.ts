import type { StoredConsent } from '../types/cookie-consent';

const STORAGE_KEY = 'guildforge_cookie_consent';
const VISITOR_ID_KEY = 'guildforge_visitor_id';

/**
 * Read the XSRF-TOKEN cookie set by Laravel for CSRF protection.
 */
function getXsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match?.[1] ? decodeURIComponent(match[1]) : null;
}

/**
 * Generate a UUID v4.
 */
function generateUUID(): string {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

/**
 * Get or create a visitor ID (persisted in localStorage).
 */
export function getOrCreateVisitorId(): string {
    try {
        let visitorId = localStorage.getItem(VISITOR_ID_KEY);
        if (!visitorId) {
            visitorId = generateUUID();
            localStorage.setItem(VISITOR_ID_KEY, visitorId);
        }
        return visitorId;
    } catch {
        // Return a session-only ID if localStorage is not available
        return generateUUID();
    }
}

/**
 * Get stored consent from localStorage (synchronous - no flash).
 */
export function getStoredConsent(): StoredConsent | null {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) return null;
        return JSON.parse(stored) as StoredConsent;
    } catch {
        return null;
    }
}

/**
 * Check if consent is needed based on config version and validity days.
 */
export function needsConsent(
    configVersion: number,
    validityDays: number,
    reconsentOnChange: boolean
): boolean {
    const consent = getStoredConsent();

    // No consent stored - needs consent
    if (!consent) return true;

    // Version mismatch and reconsent is enabled
    if (reconsentOnChange && consent.version !== configVersion) return true;

    // Check if consent has expired
    const expiryDate = new Date(consent.timestamp);
    expiryDate.setDate(expiryDate.getDate() + validityDays);

    return new Date() > expiryDate;
}

/**
 * Save consent to localStorage and sync to server.
 */
export async function saveConsent(
    preferences: Record<string, boolean>,
    configVersion: number
): Promise<void> {
    const visitorId = getOrCreateVisitorId();
    const consent: StoredConsent = {
        visitorId,
        version: configVersion,
        timestamp: new Date().toISOString(),
        preferences,
    };

    // Save to localStorage first (for immediate effect)
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
    } catch {
        // Continue even if localStorage fails
    }

    // Also set as a cookie for server-side middleware
    setCookieConsent(consent);

    // Sync to server (fire and forget)
    try {
        const headers: Record<string, string> = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        };
        const xsrfToken = getXsrfToken();
        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = xsrfToken;
        }

        await fetch('/consentimiento-cookies/consentir', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                visitor_id: visitorId,
                preferences,
                config_version: configVersion,
                consent_method: 'banner',
            }),
        });
    } catch {
        // Ignore server sync errors - localStorage consent is still valid
    }

    // Update Google Consent Mode if available
    updateConsentMode(preferences);
}

/**
 * Set consent as a cookie for server-side script injection.
 */
function setCookieConsent(consent: StoredConsent): void {
    const value = JSON.stringify(consent);
    const expires = new Date();
    expires.setFullYear(expires.getFullYear() + 1);
    document.cookie = `${STORAGE_KEY}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

/**
 * Update Google Consent Mode with new preferences.
 */
export function updateConsentMode(preferences: Record<string, boolean>): void {
    // Check if gtag function exists
    if (typeof window.gtag !== 'function') return;

    // Map category slugs to consent mode keys
    // This is a simplified mapping - the real mapping comes from server-side category config
    const consentUpdate: Record<string, 'granted' | 'denied'> = {};

    // Basic mapping based on common category slugs
    if (preferences['necessary']) {
        consentUpdate['security_storage'] = 'granted';
        consentUpdate['functionality_storage'] = 'granted';
    }

    if (preferences['preferences']) {
        consentUpdate['personalization_storage'] = 'granted';
    }

    if (preferences['analytics']) {
        consentUpdate['analytics_storage'] = 'granted';
    }

    if (preferences['marketing']) {
        consentUpdate['ad_storage'] = 'granted';
        consentUpdate['ad_user_data'] = 'granted';
        consentUpdate['ad_personalization'] = 'granted';
    }

    // Update consent mode
    window.gtag('consent', 'update', consentUpdate);
}

/**
 * Clear stored consent (for testing or user request).
 */
export function clearConsent(): void {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Ignore errors
    }

    // Clear the cookie
    document.cookie = `${STORAGE_KEY}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
}

// Extend Window interface for gtag
declare global {
    interface Window {
        gtag?: (
            command: string,
            action: string,
            params: Record<string, string>
        ) => void;
    }
}
