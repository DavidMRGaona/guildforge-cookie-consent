import { beforeEach, describe, expect, it } from 'vitest';
import { loadGoogleTagManager } from '../../resources/js/utils/google-tag-manager';

function containerScripts(): HTMLScriptElement[] {
    return Array.from(
        document.querySelectorAll<HTMLScriptElement>('script[src*="googletagmanager.com/gtm.js"]')
    );
}

describe('loadGoogleTagManager', () => {
    beforeEach(() => {
        document.head.innerHTML = '';
        document.body.innerHTML = '';
        delete window.dataLayer;
    });

    it('adds the container and starts its data layer', () => {
        expect(loadGoogleTagManager('GTM-ABC123')).toBe(true);

        const scripts = containerScripts();
        expect(scripts).toHaveLength(1);
        expect(scripts[0]?.src).toBe('https://www.googletagmanager.com/gtm.js?id=GTM-ABC123');
        expect(scripts[0]?.async).toBe(true);
        expect(window.dataLayer).toEqual([
            expect.objectContaining({ event: 'gtm.js', 'gtm.start': expect.any(Number) }),
        ]);
    });

    it('adds the container only once', () => {
        loadGoogleTagManager('GTM-ABC123');

        expect(loadGoogleTagManager('GTM-ABC123')).toBe(false);
        expect(containerScripts()).toHaveLength(1);
    });

    it('leaves alone a container the server already injected', () => {
        const injected = document.createElement('script');
        injected.src = 'https://www.googletagmanager.com/gtm.js?id=GTM-ABC123';
        document.head.appendChild(injected);

        expect(loadGoogleTagManager('GTM-ABC123')).toBe(false);
        expect(containerScripts()).toHaveLength(1);
    });

    it.each(["GTM-ABC');alert(1);//", 'G-ABC123', '', 'gtm-abc123'])(
        'refuses the malformed container ID "%s"',
        (containerId) => {
            expect(loadGoogleTagManager(containerId)).toBe(false);
            expect(containerScripts()).toHaveLength(0);
        }
    );
});
