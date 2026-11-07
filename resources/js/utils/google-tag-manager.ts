// Same rule as GtmContainerId on the server: the ID goes into a script address
const CONTAINER_ID = /^GTM-[A-Z0-9]+$/;

const CONTAINER_URL = 'https://www.googletagmanager.com/gtm.js?id=';

/**
 * Load the Google Tag Manager container on the page, as its own snippet does. Without
 * Consent Mode the server leaves it out until the visitor accepts its category, so the
 * banner loads it right after that choice instead of waiting for the next page load.
 *
 * Returns whether it added the container: never twice, nor over the server's.
 */
export function loadGoogleTagManager(containerId: string): boolean {
    if (!CONTAINER_ID.test(containerId)) return false;

    const src = CONTAINER_URL + encodeURIComponent(containerId);
    const alreadyLoaded = Array.from(document.scripts).some((script) => script.src === src);
    if (alreadyLoaded) return false;

    window.dataLayer = window.dataLayer ?? [];
    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });

    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);

    return true;
}

declare global {
    interface Window {
        dataLayer?: unknown[];
    }
}
