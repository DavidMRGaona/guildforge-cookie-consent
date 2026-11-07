import type { BannerConfig, CookieCategory } from '../types/cookie-consent';

/**
 * Whether a new choice needs a fresh page. The server injects the scripts of the accepted
 * categories when it renders the page, so they appear only after a reload once accepted,
 * and a reload is the only way to stop them once withdrawn.
 *
 * @param before - the choice this page runs with (none until the visitor chooses)
 * @param after - the choice just saved
 */
export function choiceNeedsReload(
    categories: CookieCategory[],
    before: Record<string, boolean>,
    after: Record<string, boolean>,
    config: BannerConfig
): boolean {
    const changed = (slug: string): boolean => (before[slug] === true) !== (after[slug] === true);

    if (categories.some((category) => category.hasScripts && changed(category.slug))) {
        return true;
    }

    // Without Consent Mode the container is loaded once its category is accepted, and stays
    return (
        config.gtmEnabled &&
        !config.consentModeEnabled &&
        before[config.gtmCategory] === true &&
        after[config.gtmCategory] !== true
    );
}
