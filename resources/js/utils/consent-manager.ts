import type { ConsentModeValue, StoredConsent } from '../types/cookie-consent';
import { csrfHeaders } from '@/utils/csrf';

const STORAGE_KEY = 'guildforge_cookie_consent';
const VISITOR_ID_KEY = 'guildforge_visitor_id';

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
 * Check whether the visitor consented to a category (used by other modules' frontends).
 * Required categories always count as consented; others need a stored, valid
 * consent (same expiry and version rules as the banner) with the slug set to true.
 */
export function hasConsentFor(
    slug: string,
    configVersion: number,
    validityDays: number,
    reconsentOnChange: boolean,
    requiredSlugs: string[]
): boolean {
    if (requiredSlugs.includes(slug)) return true;

    if (needsConsent(configVersion, validityDays, reconsentOnChange)) return false;

    return getStoredConsent()?.preferences?.[slug] === true;
}

/**
 * The server did not record the choice, so it was not applied either.
 */
export class ConsentNotSavedError extends Error {
    constructor(message: string, options?: ErrorOptions) {
        super(message, options);
        this.name = 'ConsentNotSavedError';
    }
}

/**
 * Record the choice on the server and then apply it in this browser. A choice the server
 * did not record is not applied: every applied consent can be shown later, and until the
 * visitor tries again nothing that needs consent runs.
 *
 * @throws ConsentNotSavedError when the server cannot be reached or refuses the choice
 */
export async function saveConsent(
    preferences: Record<string, boolean>,
    configVersion: number
): Promise<void> {
    const visitorId = getOrCreateVisitorId();

    let response: Response;
    try {
        response = await fetch('/consentimiento-cookies/consentir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...csrfHeaders(),
            },
            body: JSON.stringify({
                visitor_id: visitorId,
                preferences,
                config_version: configVersion,
                consent_method: 'banner',
            }),
        });
    } catch (error) {
        throw new ConsentNotSavedError('The server could not be reached', { cause: error });
    }

    if (!response.ok) {
        throw new ConsentNotSavedError(`The server answered ${response.status}`);
    }

    const consent: StoredConsent = {
        visitorId,
        version: configVersion,
        timestamp: new Date().toISOString(),
        preferences,
    };

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
    } catch {
        // The cookie below still carries the choice
    }

    // Also set as a cookie for server-side middleware
    setCookieConsent(consent);
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
 * Tell Google Consent Mode the visitor's choice (see consentModeUpdate()). Every key is
 * sent, so withdrawn ones are denied again.
 */
export function updateConsentMode(update: Record<string, ConsentModeValue>): void {
    if (typeof window.gtag !== 'function') return;

    window.gtag('consent', 'update', update);
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
        gtag?: (command: string, action: string, params: Record<string, string>) => void;
    }
}
