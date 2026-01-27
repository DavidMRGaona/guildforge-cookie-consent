<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum BannerPosition: string implements HasLabel
{
    case Bottom = 'bottom';
    case Top = 'top';
    case BottomLeft = 'bottom_left';
    case BottomRight = 'bottom_right';
    case Center = 'center';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bottom => __('cookie-consent::cookie_consent.banner_position.bottom'),
            self::Top => __('cookie-consent::cookie_consent.banner_position.top'),
            self::BottomLeft => __('cookie-consent::cookie_consent.banner_position.bottom_left'),
            self::BottomRight => __('cookie-consent::cookie_consent.banner_position.bottom_right'),
            self::Center => __('cookie-consent::cookie_consent.banner_position.center'),
        };
    }
}
