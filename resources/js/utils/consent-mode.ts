import type { CookieCategory, ConsentModeValue } from '../types/cookie-consent';

/**
 * The Consent Mode state for a choice: every key starts at its default and the keys of
 * each accepted category are granted. Required categories count as accepted even when the
 * choice leaves them out. The server maps the same way (ConsentModeService::mapToConsentMode),
 * from the same categories and defaults, so both always agree.
 */
export function consentModeUpdate(
    categories: CookieCategory[],
    preferences: Record<string, boolean>,
    defaults: Record<string, ConsentModeValue>
): Record<string, ConsentModeValue> {
    const update: Record<string, ConsentModeValue> = { ...defaults };

    for (const category of categories) {
        if (!(preferences[category.slug] ?? category.isRequired)) continue;

        for (const key of category.consentModeKeys) {
            update[key] = 'granted';
        }
    }

    return update;
}
