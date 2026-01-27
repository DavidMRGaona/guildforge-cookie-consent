<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Illuminate\Database\Seeder;

final class CookieConsentSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Appearance
            'banner_position' => 'bottom',
            'banner_layout' => 'bar',
            'banner_theme' => 'light',
            'primary_color' => '#D97706',     // Amber-600
            'secondary_color' => '#57534E',   // Stone-600
            'background_color' => '#FFFFFF',
            'text_color' => '#1C1917',        // Stone-900
            'show_logo' => '1',

            // Texts
            'banner_title' => 'Utilizamos cookies',
            'banner_description' => 'Usamos cookies propias y de terceros para mejorar tu experiencia, analizar el tráfico y mostrarte contenido personalizado. Puedes aceptar todas las cookies, rechazarlas o configurar tus preferencias.',
            'accept_all_text' => 'Aceptar todas',
            'reject_all_text' => 'Rechazar todas',
            'settings_text' => 'Configurar',
            'save_settings_text' => 'Guardar preferencias',
            'privacy_policy_link_text' => 'Política de privacidad',
            'privacy_policy_url' => '/politica-de-privacidad',

            // Behavior
            'show_reject_all' => '1',
            'show_settings_button' => '1',
            'block_page_until_consent' => '0',
            'consent_validity_days' => '365',
            'reconsent_on_change' => '1',

            // Integrations
            'gtm_enabled' => '0',
            'consent_mode_enabled' => '1',
            'config_version' => '1',
        ];

        foreach ($settings as $key => $value) {
            SettingModel::updateOrCreate(
                ['key' => 'cookie-consent.'.$key],
                ['value' => $value]
            );
        }
    }
}
