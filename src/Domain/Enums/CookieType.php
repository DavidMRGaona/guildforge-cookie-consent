<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum CookieType: string implements HasLabel
{
    case FirstParty = 'first_party';
    case ThirdParty = 'third_party';

    public function getLabel(): string
    {
        return match ($this) {
            self::FirstParty => __('cookie-consent::cookie_consent.cookie_type.first_party'),
            self::ThirdParty => __('cookie-consent::cookie_consent.cookie_type.third_party'),
        };
    }
}
