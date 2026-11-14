import type { BannerConfig } from '../types/cookie-consent';

/**
 * The site mode a theme fixes on the banner and the settings window, as `data-color-scheme`.
 *
 * With it the core gives them its own tokens of that mode (core#640): the palette chosen in the
 * panel, with the text and primary colours it computes for it. The module keeps no copy of any
 * colour, so a palette change reaches the banner as it reaches the site. «Automático» fixes none
 * and follows the site mode; «Personalizado» paints with the panel's colours.
 */
export function bannerColorScheme(theme: BannerConfig['theme']): 'light' | 'dark' | undefined {
    if (theme === 'light' || theme === 'dark') return theme;

    return undefined;
}
