<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum BannerTheme: string implements HasLabel
{
    case Light = 'light';
    case Dark = 'dark';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Light => __('cookie-consent::cookie_consent.banner_theme.light'),
            self::Dark => __('cookie-consent::cookie_consent.banner_theme.dark'),
            self::Custom => __('cookie-consent::cookie_consent.banner_theme.custom'),
        };
    }
}
