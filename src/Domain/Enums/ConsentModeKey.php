<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Google Consent Mode v2 keys.
 *
 * @see https://support.google.com/tagmanager/answer/10718549
 */
enum ConsentModeKey: string implements HasLabel
{
    case AdStorage = 'ad_storage';
    case AdUserData = 'ad_user_data';
    case AdPersonalization = 'ad_personalization';
    case AnalyticsStorage = 'analytics_storage';
    case FunctionalityStorage = 'functionality_storage';
    case PersonalizationStorage = 'personalization_storage';
    case SecurityStorage = 'security_storage';

    public function getLabel(): string
    {
        return match ($this) {
            self::AdStorage => __('cookie-consent::cookie_consent.consent_mode.ad_storage'),
            self::AdUserData => __('cookie-consent::cookie_consent.consent_mode.ad_user_data'),
            self::AdPersonalization => __('cookie-consent::cookie_consent.consent_mode.ad_personalization'),
            self::AnalyticsStorage => __('cookie-consent::cookie_consent.consent_mode.analytics_storage'),
            self::FunctionalityStorage => __('cookie-consent::cookie_consent.consent_mode.functionality_storage'),
            self::PersonalizationStorage => __('cookie-consent::cookie_consent.consent_mode.personalization_storage'),
            self::SecurityStorage => __('cookie-consent::cookie_consent.consent_mode.security_storage'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AdStorage => __('cookie-consent::cookie_consent.consent_mode.ad_storage_description'),
            self::AdUserData => __('cookie-consent::cookie_consent.consent_mode.ad_user_data_description'),
            self::AdPersonalization => __('cookie-consent::cookie_consent.consent_mode.ad_personalization_description'),
            self::AnalyticsStorage => __('cookie-consent::cookie_consent.consent_mode.analytics_storage_description'),
            self::FunctionalityStorage => __('cookie-consent::cookie_consent.consent_mode.functionality_storage_description'),
            self::PersonalizationStorage => __('cookie-consent::cookie_consent.consent_mode.personalization_storage_description'),
            self::SecurityStorage => __('cookie-consent::cookie_consent.consent_mode.security_storage_description'),
        };
    }

    /**
     * Get default consent value (denied for all except security_storage).
     */
    public function defaultValue(): string
    {
        return match ($this) {
            self::SecurityStorage => 'granted',
            default => 'denied',
        };
    }
}
