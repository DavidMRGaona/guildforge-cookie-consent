import type { BannerConfig } from '../types/cookie-consent';

/**
 * Colour tokens of the «Claro» and «Oscuro» themes, the same ones the site defines for its
 * light and dark modes (the semantic tokens of the core's stylesheet).
 *
 * They are set on the banner and the settings window themselves, so each theme looks the same
 * whatever mode the visitor's site is in. «Automático» sets none and follows the site mode. The utilities the components use (`bg-surface`,
 * `text-base-primary`, `border-default`, `text-primary`...) read these variables.
 */
const LIGHT: Record<string, string> = {
    '--color-bg-surface': '#ffffff',
    '--color-bg-elevated': '#ffffff',
    '--color-bg-muted': '#f5f5f4',
    '--color-text-primary': '#1c1917',
    '--color-text-secondary': '#57534e',
    '--color-text-muted': '#756f6b',
    '--color-border': '#e7e5e4',
    '--color-border-strong': '#d6d3d1',
    '--color-primary': '#bb5b00',
    '--color-primary-link': '#b45500',
    '--color-secondary': '#57534e',
};

const DARK: Record<string, string> = {
    '--color-bg-surface': '#302d2a',
    '--color-bg-elevated': '#3d3936',
    '--color-bg-muted': '#3d3936',
    '--color-text-primary': '#f5f5f4',
    '--color-text-secondary': '#d6d3d1',
    '--color-text-muted': '#a8a29e',
    '--color-border': '#44403c',
    '--color-border-strong': '#57534e',
    '--color-primary': '#b36100',
    '--color-primary-link': '#f59e0b',
    '--color-secondary': '#a8a29e',
};

/**
 * The variables a theme sets; none for «Automático», which keeps the site's own (light or dark)
 * tokens, nor for «Personalizado», which paints with the panel's colours.
 */
export function bannerThemeVariables(theme: BannerConfig['theme']): Record<string, string> {
    if (theme === 'light') return { ...LIGHT };
    if (theme === 'dark') return { ...DARK };

    return {};
}
