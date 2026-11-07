import type { CookieCategory } from '../types/cookie-consent';
import { CONSENT_COOKIE_NAME } from './consent-manager';

// A cookie name as browsers take it (RFC 6265 token): nothing that could add attributes
const COOKIE_NAME = /^[!#$%&'*+\-.^_`|~0-9A-Za-z]+$/;

const EXPIRED = 'Thu, 01 Jan 1970 00:00:00 GMT';

/**
 * Delete the declared cookies of every category the visitor did not accept, so withdrawing
 * consent also removes what was stored with it. Required categories and the consent cookie
 * are never touched. A name ending in "*" stands for every cookie starting that way
 * ("_ga_*"). Only cookies of this site can go: those of other sites, and HttpOnly ones,
 * are out of reach of the page.
 */
export function deleteCookiesOfRejected(
    categories: CookieCategory[],
    preferences: Record<string, boolean>
): void {
    for (const category of categories) {
        if (category.isRequired || preferences[category.slug] === true) continue;

        for (const cookie of category.cookies) {
            for (const name of matchingNames(cookie.name.trim())) {
                deleteCookie(name);
            }
        }
    }
}

/**
 * The domains a cookie of this page can belong to: the host itself and each parent domain,
 * without the top level one ("www.example.org" → "www.example.org", "example.org"). Scripts
 * such as analytics store theirs on a parent domain. None for "localhost" or an IP address,
 * where only host cookies exist.
 */
export function cookieDomains(hostname: string): string[] {
    const isIp = /^[\d.]+$/.test(hostname) || hostname.includes(':');
    const parts = hostname.split('.');
    if (isIp || parts.length < 2) return [];

    return parts.slice(0, -1).map((_, index) => parts.slice(index).join('.'));
}

function matchingNames(declared: string): string[] {
    if (!COOKIE_NAME.test(declared)) return [];

    const names = declared.endsWith('*')
        ? storedNames().filter((name) => {
              const prefix = declared.slice(0, -1);

              return prefix !== '' && name.startsWith(prefix);
          })
        : [declared];

    return names.filter((name) => name !== CONSENT_COOKIE_NAME);
}

function storedNames(): string[] {
    return document.cookie
        .split(';')
        .map((pair) => pair.split('=')[0]?.trim() ?? '')
        .filter((name) => COOKIE_NAME.test(name));
}

function deleteCookie(name: string): void {
    document.cookie = `${name}=; expires=${EXPIRED}; path=/`;

    for (const domain of cookieDomains(window.location.hostname)) {
        document.cookie = `${name}=; expires=${EXPIRED}; path=/; domain=${domain}`;
    }
}
