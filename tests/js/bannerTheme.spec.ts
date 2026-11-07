import { describe, expect, it } from 'vitest';
import { bannerThemeVariables } from '../../resources/js/utils/banner-theme';

function luminance(hex: string): number {
    const channels = [1, 3, 5].map((start) => {
        const value = parseInt(hex.slice(start, start + 2), 16) / 255;

        return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
    });

    return 0.2126 * channels[0]! + 0.7152 * channels[1]! + 0.0722 * channels[2]!;
}

function contrast(first: string, second: string): number {
    const [lighter, darker] = [luminance(first), luminance(second)].sort((a, b) => b - a);

    return (lighter! + 0.05) / (darker! + 0.05);
}

describe('bannerThemeVariables', () => {
    it('gives the light and dark themes different backgrounds and texts', () => {
        const light = bannerThemeVariables('light');
        const dark = bannerThemeVariables('dark');

        expect(light['--color-bg-surface']).not.toBe(dark['--color-bg-surface']);
        expect(light['--color-text-primary']).not.toBe(dark['--color-text-primary']);
        expect(luminance(light['--color-bg-surface']!)).toBeGreaterThan(
            luminance(light['--color-text-primary']!)
        );
        expect(luminance(dark['--color-bg-surface']!)).toBeLessThan(
            luminance(dark['--color-text-primary']!)
        );
    });

    it('leaves the colours to the panel with the custom theme', () => {
        expect(bannerThemeVariables('custom')).toEqual({});
    });

    it('leaves the colours to the site mode with the automatic theme', () => {
        expect(bannerThemeVariables('auto')).toEqual({});
    });

    it.each(['light', 'dark'] as const)('reaches AA contrast with the %s theme', (theme) => {
        const tokens = bannerThemeVariables(theme);
        const background = ['--color-bg-surface', '--color-bg-muted', '--color-bg-elevated'];
        const texts = [
            '--color-text-primary',
            '--color-text-secondary',
            '--color-text-muted',
            '--color-primary-link',
        ];

        for (const surface of background) {
            for (const text of texts) {
                expect(
                    contrast(tokens[surface]!, tokens[text]!),
                    `${text} on ${surface}`
                ).toBeGreaterThanOrEqual(4.5);
            }
        }
        // White text on the primary button
        expect(contrast('#ffffff', tokens['--color-primary']!)).toBeGreaterThanOrEqual(4.5);
    });
});
