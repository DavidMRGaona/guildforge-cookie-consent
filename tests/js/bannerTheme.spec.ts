import { describe, expect, it } from 'vitest';
import { bannerColorScheme } from '../../resources/js/utils/banner-theme';

describe('bannerColorScheme', () => {
    it('fixes the light and dark modes for the «Claro» and «Oscuro» themes', () => {
        expect(bannerColorScheme('light')).toBe('light');
        expect(bannerColorScheme('dark')).toBe('dark');
    });

    it('fixes no mode with the custom theme, which paints with the panel colours', () => {
        expect(bannerColorScheme('custom')).toBeUndefined();
    });

    it('fixes no mode with the automatic theme, which follows the site mode', () => {
        expect(bannerColorScheme('auto')).toBeUndefined();
    });
});
