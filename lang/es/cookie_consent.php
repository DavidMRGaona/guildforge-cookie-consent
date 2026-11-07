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
            'created_at' => 'Creada',
        ],
        'hints' => [
            'is_required' => 'Las categorías requeridas no pueden ser rechazadas por el usuario',
        ],
        'sections' => [
            'consent_mode' => 'Google Consent Mode',
            'consent_mode_description' => 'Selecciona las claves de Google Consent Mode v2 asociadas a esta categoría',
        ],
    ],

    'cookies' => [
        'label' => 'cookie',
        'plural_label' => 'Cookies',
        'fields' => [
            'name' => 'Nombre',
            'name_help' => 'El nombre exacto de la cookie. Si cambia el final, termínalo en * (_ga_* vale para todas las que empiezan por _ga_).',
            'provider' => 'Proveedor',
            'domain' => 'Dominio',
            'purpose' => 'Propósito',
            'type' => 'Tipo',
            'duration' => 'Duración',
            'is_active' => 'Activa',
        ],
        'placeholders' => [
            'name' => '_ga, _fbp, session_id...',
            'provider' => 'Google, Meta, Propio...',
            'domain' => '.google.com, .facebook.com...',
            'duration' => 'Sesión, 1 año, 2 años...',
        ],
    ],

    'scripts' => [
        'label' => 'script',
        'plural_label' => 'Scripts',
        'sections' => [
            'scripts' => 'Scripts',
            'scripts_description' => 'Pega el fragmento completo tal como lo da el proveedor, con sus etiquetas <script>. Se inserta sin modificar.',
        ],
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
        'placeholders' => [
            'name' => 'Google Analytics, GTM, Meta Pixel...',
            'description' => 'Notas internas sobre este script...',
        ],
        'hints' => [
            'script_head' => 'Scripts que se cargan en la sección <head> del documento',
            'script_body_start' => 'Scripts que se cargan justo después de la etiqueta <body>',
            'script_body_end' => 'Scripts que se cargan antes de cerrar </body>',
            'noscript_content' => 'Contenido para usuarios sin JavaScript (ej: iframe de GTM). Se inserta justo después de <body>',
        ],
    ],

    'consents' => [
        'label' => 'consentimiento',
        'plural_label' => 'Consentimientos',
        'anonymous' => 'Anónimo',
        'sections' => [
            'visitor' => 'Información del visitante',
            'consent' => 'Consentimiento',
        ],
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
        'filters' => [
            'authenticated' => 'Usuario autenticado',
            'consented_today' => 'Hoy',
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
        'auto' => 'Automático',
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
        'navigation' => 'Banner de cookies',
        'title' => 'Ajustes del banner de cookies',
        'saved' => 'Ajustes guardados',

        'appearance' => 'Apariencia',
        'banner_position' => 'Posición del banner',
        'banner_layout' => 'Diseño del banner',
        'banner_theme' => 'Tema del banner',
        'primary_color' => 'Color primario',
        'secondary_color' => 'Color secundario',
        'background_color' => 'Color de fondo',
        'text_color' => 'Color de texto',

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
        'privacy_policy_url_help' => 'Una página de este sitio (por ejemplo, /politica-de-privacidad) o una dirección completa que empiece por https://',
        'privacy_policy_url_invalid' => 'Escribe una ruta que empiece por / o una dirección que empiece por http:// o https://.',

        'behavior' => 'Comportamiento',
        'show_reject_all' => 'Mostrar botón "Rechazar todas"',
        'show_settings_button' => 'Mostrar botón "Configurar"',
        'show_floating_button' => 'Mostrar botón flotante de preferencias',
        'show_floating_button_help' => 'Un botón pequeño con una galleta, abajo a la izquierda, abre las preferencias de cookies una vez elegidas. Si añades el enlace «Preferencias de cookies» al pie de página desde Navegación, puedes quitarlo.',
        'consent_validity_days' => 'Días de validez del consentimiento',
        'consent_validity_days_help' => 'Número de días que el consentimiento es válido antes de volver a solicitarlo',
        'reconsent_on_change' => 'Volver a solicitar consentimiento al cambiar configuración',
        'reconsent_on_change_help' => 'Si se añaden o modifican categorías/cookies, mostrar el banner de nuevo',

        'integrations' => 'Integraciones',
        'gtm_enabled' => 'Habilitar Google Tag Manager',
        'gtm_container_id' => 'ID del contenedor GTM',
        'gtm_container_id_invalid' => 'El ID del contenedor debe tener la forma GTM-XXXXXX.',
        'gtm_category' => 'Categoría de Google Tag Manager',
        'gtm_category_help' => 'Sin Google Consent Mode, el contenedor no se carga hasta que el visitante acepta esta categoría. Con Consent Mode se carga siempre y respeta lo que acepte.',
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
    // Pages offered for the site menus
    'routes' => [
        'preferences' => 'Preferencias de cookies',
    ],

    'frontend' => [
        'preferences' => 'Preferencias de cookies',
        'save_error' => 'No hemos podido guardar tu elección. Vuelve a intentarlo y, si sigue fallando, recarga la página.',
        'default_title' => 'Utilizamos cookies',
        'default_description' => 'Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.',
        'accept_all' => 'Aceptar todas',
        'reject_all' => 'Rechazar todas',
        'settings' => 'Configurar',
        'save_preferences' => 'Guardar preferencias',
        'privacy_policy' => 'Política de privacidad',
        'required_category' => 'Obligatoria',
        'show_cookies' => 'Ver cookies',
        'hide_cookies' => 'Ocultar cookies',
        'cookie_column' => 'Cookie',
        'provider_column' => 'Proveedor',
        'duration_column' => 'Duración',
        'cancel' => 'Cancelar',
        'close' => 'Cerrar',
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
