<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Cookies',
    'navigation_group' => 'Configuración',

    // Resources
    'categories' => [
        'label' => 'categoría de cookies',
        'plural_label' => 'Categorías de cookies',
        'fields' => [
            'name' => 'Nombre',
            'slug' => 'Identificador',
            'description' => 'Descripción',
            'is_required' => 'Requerida',
            'sort_order' => 'Orden',
            'consent_mode_keys' => 'Claves de Consent Mode',
            'cookies_count' => 'Cookies',
            'scripts_count' => 'Scripts',
        ],
    ],

    'cookies' => [
        'label' => 'cookie',
        'plural_label' => 'Cookies',
        'fields' => [
            'name' => 'Nombre',
            'provider' => 'Proveedor',
            'domain' => 'Dominio',
            'purpose' => 'Propósito',
            'type' => 'Tipo',
            'duration' => 'Duración',
            'is_active' => 'Activa',
        ],
    ],

    'scripts' => [
        'label' => 'script',
        'plural_label' => 'Scripts',
        'fields' => [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'script_head' => 'Script en <head>',
            'script_body_start' => 'Script después de <body>',
            'script_body_end' => 'Script antes de </body>',
            'noscript_content' => 'Contenido <noscript>',
            'sort_order' => 'Orden',
            'is_active' => 'Activo',
        ],
        'hints' => [
            'script_head' => 'Scripts que se cargan en la sección <head> del documento',
            'script_body_start' => 'Scripts que se cargan justo después de la etiqueta <body>',
            'script_body_end' => 'Scripts que se cargan antes de cerrar </body>',
            'noscript_content' => 'Contenido para usuarios sin JavaScript (ej: iframe de GTM)',
        ],
    ],

    'consents' => [
        'label' => 'consentimiento',
        'plural_label' => 'Consentimientos',
        'fields' => [
            'visitor_id' => 'ID visitante',
            'user' => 'Usuario',
            'ip_hash' => 'Hash IP',
            'user_agent' => 'User Agent',
            'preferences' => 'Preferencias',
            'config_version' => 'Versión config',
            'consent_method' => 'Método',
            'consented_at' => 'Fecha consentimiento',
            'expires_at' => 'Expira',
        ],
    ],

    // Cookie types
    'cookie_type' => [
        'first_party' => 'Primera parte',
        'third_party' => 'Tercera parte',
    ],

    // Banner positions
    'banner_position' => [
        'bottom' => 'Inferior',
        'top' => 'Superior',
        'bottom_left' => 'Inferior izquierda',
        'bottom_right' => 'Inferior derecha',
        'center' => 'Centro',
    ],

    // Banner layouts
    'banner_layout' => [
        'bar' => 'Barra',
        'box' => 'Caja',
        'modal' => 'Modal',
    ],

    // Banner themes
    'banner_theme' => [
        'light' => 'Claro',
        'dark' => 'Oscuro',
        'custom' => 'Personalizado',
    ],

    // Consent methods
    'consent_method' => [
        'banner' => 'Banner',
        'settings_page' => 'Página de ajustes',
        'api' => 'API',
    ],

    // Consent Mode keys
    'consent_mode' => [
        'ad_storage' => 'Almacenamiento de anuncios',
        'ad_storage_description' => 'Permite almacenamiento relacionado con publicidad',
        'ad_user_data' => 'Datos de usuario para anuncios',
        'ad_user_data_description' => 'Permite enviar datos de usuario a Google para publicidad',
        'ad_personalization' => 'Personalización de anuncios',
        'ad_personalization_description' => 'Permite anuncios personalizados',
        'analytics_storage' => 'Almacenamiento analítico',
        'analytics_storage_description' => 'Permite almacenamiento para análisis de uso',
        'functionality_storage' => 'Almacenamiento funcional',
        'functionality_storage_description' => 'Permite almacenamiento para funcionalidades del sitio',
        'personalization_storage' => 'Almacenamiento de personalización',
        'personalization_storage_description' => 'Permite almacenamiento para personalización',
        'security_storage' => 'Almacenamiento de seguridad',
        'security_storage_description' => 'Permite almacenamiento para funciones de seguridad',
    ],

    // Settings
    'settings' => [
        'appearance' => 'Apariencia',
        'banner_position' => 'Posición del banner',
        'banner_layout' => 'Diseño del banner',
        'banner_theme' => 'Tema del banner',
        'primary_color' => 'Color primario',
        'secondary_color' => 'Color secundario',
        'background_color' => 'Color de fondo',
        'text_color' => 'Color de texto',
        'show_logo' => 'Mostrar logo',

        'texts' => 'Textos',
        'texts_description' => 'Personaliza los textos que se muestran en el banner de cookies',
        'banner_title' => 'Título del banner',
        'banner_description' => 'Descripción del banner',
        'accept_all_text' => 'Texto "Aceptar todas"',
        'reject_all_text' => 'Texto "Rechazar todas"',
        'settings_text' => 'Texto "Configurar"',
        'save_settings_text' => 'Texto "Guardar preferencias"',
        'privacy_policy_link_text' => 'Texto del enlace a la política de privacidad',
        'privacy_policy_url' => 'URL de la política de privacidad',

        'behavior' => 'Comportamiento',
        'show_reject_all' => 'Mostrar botón "Rechazar todas"',
        'show_settings_button' => 'Mostrar botón "Configurar"',
        'block_page_until_consent' => 'Bloquear página hasta consentimiento',
        'block_page_until_consent_help' => 'Muestra un overlay que impide interactuar con la página hasta que el usuario dé su consentimiento',
        'consent_validity_days' => 'Días de validez del consentimiento',
        'consent_validity_days_help' => 'Número de días que el consentimiento es válido antes de volver a solicitarlo',
        'reconsent_on_change' => 'Volver a solicitar consentimiento al cambiar configuración',
        'reconsent_on_change_help' => 'Si se añaden o modifican categorías/cookies, mostrar el banner de nuevo',

        'integrations' => 'Integraciones',
        'gtm_enabled' => 'Habilitar Google Tag Manager',
        'gtm_container_id' => 'ID del contenedor GTM',
        'consent_mode_enabled' => 'Habilitar Google Consent Mode v2',
        'consent_mode_enabled_help' => 'Envía el estado del consentimiento a Google para cumplir con la normativa',
    ],

    // Permissions
    'permissions' => [
        'categories_view_any' => 'Ver categorías de cookies',
        'categories_create' => 'Crear categorías de cookies',
        'categories_update' => 'Editar categorías de cookies',
        'categories_delete' => 'Eliminar categorías de cookies',
        'consents_view_any' => 'Ver consentimientos',
        'consents_export' => 'Exportar consentimientos',
    ],

    // Widgets
    'widgets' => [
        'stats' => [
            'title' => 'Estadísticas de consentimiento',
            'total_consents' => 'Total consentimientos',
            'by_method' => 'Por método',
        ],
    ],

    // Frontend texts (used in Vue components)
    'frontend' => [
        'default_title' => 'Utilizamos cookies',
        'default_description' => 'Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.',
        'accept_all' => 'Aceptar todas',
        'reject_all' => 'Rechazar todas',
        'settings' => 'Configurar',
        'save_preferences' => 'Guardar preferencias',
        'privacy_policy' => 'Política de privacidad',
        'required_category' => '(Obligatoria)',
        'show_cookies' => 'Ver cookies',
        'hide_cookies' => 'Ocultar cookies',
    ],

    // Validation messages
    'validation' => [
        'visitor_id_required' => 'El identificador del visitante es obligatorio.',
        'visitor_id_uuid' => 'El identificador del visitante debe ser un UUID válido.',
        'preferences_required' => 'Las preferencias de cookies son obligatorias.',
        'preferences_array' => 'Las preferencias deben ser un array.',
        'config_version_required' => 'La versión de configuración es obligatoria.',
    ],
];
