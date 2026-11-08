<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Illuminate\Database\Seeder;
use Modules\CookieConsent\Infrastructure\Services\BannerTextDefaults;

final class CookieConsentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Appearance
            'banner_position' => 'bottom',
            'banner_layout' => 'bar',
            'banner_theme' => 'auto',
            'primary_color' => '#D97706',     // Amber-600
            'secondary_color' => '#57534E',   // Stone-600
            'background_color' => '#FFFFFF',
            'text_color' => '#1C1917',        // Stone-900

            // Texts: the banner ones in the language of the site
            ...BannerTextDefaults::all(),
            'privacy_policy_url' => '/politica-de-privacidad',

            // Behavior
            'show_reject_all' => '1',
            'show_settings_button' => '1',
            'show_floating_button' => '1',
            'consent_validity_days' => '365',
            'reconsent_on_change' => '1',

            // Integrations
            'gtm_enabled' => '0',
            'consent_mode_enabled' => '1',
            'config_version' => '1',
        ];

        // Only add missing settings: never reset what an admin configured
        foreach ($settings as $key => $value) {
            SettingModel::firstOrCreate(
                ['key' => 'cookie-consent.'.$key],
                ['value' => $value]
            );
        }
    }
}
