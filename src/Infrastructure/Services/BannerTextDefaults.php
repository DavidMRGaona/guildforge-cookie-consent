<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

/**
 * The banner texts a site shows until its admin writes its own, in the language of the site.
 *
 * One list for everything that proposes them (the settings form and the seeder), so a new
 * site and one that never touched a text say the same thing. Settings already stored are
 * never touched: they are the site's own words.
 */
final class BannerTextDefaults
{
    /**
     * Setting name => key under `frontend` in the module's translations.
     *
     * @var array<string, string>
     */
    private const array TRANSLATION_KEYS = [
        'banner_title' => 'default_title',
        'banner_description' => 'default_description',
        'accept_all_text' => 'accept_all',
        'reject_all_text' => 'reject_all',
        'settings_text' => 'settings',
        'save_settings_text' => 'save_preferences',
        'privacy_policy_link_text' => 'privacy_policy',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return array_map(
            static fn (string $key): string => (string) __("cookie-consent::cookie_consent.frontend.{$key}"),
            self::TRANSLATION_KEYS,
        );
    }

    /**
     * The default of one text setting, or null when the setting is not one of the banner texts.
     */
    public static function for(string $setting): ?string
    {
        return self::all()[$setting] ?? null;
    }
}
