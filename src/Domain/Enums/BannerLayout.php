<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum BannerLayout: string implements HasLabel
{
    case Bar = 'bar';
    case Box = 'box';
    case Modal = 'modal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bar => __('cookie-consent::cookie_consent.banner_layout.bar'),
            self::Box => __('cookie-consent::cookie_consent.banner_layout.box'),
            self::Modal => __('cookie-consent::cookie_consent.banner_layout.modal'),
        };
    }
}
