<?php

declare(strict_types=1);

return [
    // Appearance
    'banner_position' => 'bottom',
    'banner_layout' => 'bar',
    'banner_theme' => 'light',
    'primary_color' => '#10B981',
    'secondary_color' => '#6B7280',
    'background_color' => '#FFFFFF',
    'text_color' => '#1F2937',
    'show_logo' => true,

    // Texts
    'banner_title' => 'Utilizamos cookies',
    'banner_description' => 'Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.',
    'accept_all_text' => 'Aceptar todas',
    'reject_all_text' => 'Rechazar todas',
    'settings_text' => 'Configurar',
    'save_settings_text' => 'Guardar preferencias',
    'privacy_policy_link_text' => 'Política de privacidad',
    'privacy_policy_url' => '/politica-de-privacidad',

    // Behavior
    'show_reject_all' => true,
    'show_settings_button' => true,
    'block_page_until_consent' => false,
    'consent_validity_days' => 365,
    'reconsent_on_change' => true,

    // Integrations
    'gtm_enabled' => false,
    'gtm_container_id' => '',
    'consent_mode_enabled' => true,

    // Config version (auto-incremented on category/cookie/script changes)
    'config_version' => 1,
];
