<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConsentMethod: string implements HasLabel
{
    case Banner = 'banner';
    case SettingsPage = 'settings_page';
    case Api = 'api';

    public function getLabel(): string
    {
        return match ($this) {
            self::Banner => __('cookie-consent::cookie_consent.consent_method.banner'),
            self::SettingsPage => __('cookie-consent::cookie_consent.consent_method.settings_page'),
            self::Api => __('cookie-consent::cookie_consent.consent_method.api'),
        };
    }
}
