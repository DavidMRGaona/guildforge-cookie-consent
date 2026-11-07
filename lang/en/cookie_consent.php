<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation' => 'Cookies',
    'navigation_group' => 'Settings',

    // Resources
    'categories' => [
        'label' => 'cookie category',
        'plural_label' => 'Cookie categories',
        'fields' => [
            'name' => 'Name',
            'slug' => 'Identifier',
            'description' => 'Description',
            'is_required' => 'Required',
            'sort_order' => 'Order',
            'consent_mode_keys' => 'Consent Mode keys',
            'cookies_count' => 'Cookies',
            'scripts_count' => 'Scripts',
            'created_at' => 'Created',
        ],
        'hints' => [
            'is_required' => 'Required categories cannot be declined by the visitor',
        ],
        'sections' => [
            'consent_mode' => 'Google Consent Mode',
            'consent_mode_description' => 'Pick the Google Consent Mode v2 keys tied to this category',
        ],
    ],

    'cookies' => [
        'label' => 'cookie',
        'plural_label' => 'Cookies',
        'fields' => [
            'name' => 'Name',
            'name_help' => 'The exact name of the cookie. If its ending changes, end it with * (_ga_* covers every cookie that starts with _ga_).',
            'provider' => 'Provider',
            'domain' => 'Domain',
            'purpose' => 'Purpose',
            'type' => 'Type',
            'duration' => 'Duration',
            'is_active' => 'Active',
        ],
        'placeholders' => [
            'name' => '_ga, _fbp, session_id...',
            'provider' => 'Google, Meta, Own...',
            'domain' => '.google.com, .facebook.com...',
            'duration' => 'Session, 1 year, 2 years...',
        ],
    ],

    'scripts' => [
        'label' => 'script',
        'plural_label' => 'Scripts',
        'sections' => [
            'scripts' => 'Scripts',
            'scripts_description' => 'Paste the whole snippet as the provider gives it, with its <script> tags. It is inserted unchanged.',
        ],
        'fields' => [
            'name' => 'Name',
            'description' => 'Description',
            'script_head' => 'Script in <head>',
            'script_body_start' => 'Script after <body>',
            'script_body_end' => 'Script before </body>',
            'noscript_content' => '<noscript> content',
            'sort_order' => 'Order',
            'is_active' => 'Active',
        ],
        'placeholders' => [
            'name' => 'Google Analytics, GTM, Meta Pixel...',
            'description' => 'Internal notes about this script...',
        ],
        'hints' => [
            'script_head' => 'Scripts loaded in the <head> section of the document',
            'script_body_start' => 'Scripts loaded right after the <body> tag',
            'script_body_end' => 'Scripts loaded before </body> closes',
            'noscript_content' => 'Content for visitors without JavaScript (e.g. the GTM iframe). It is inserted right after <body>',
        ],
    ],

    'consents' => [
        'label' => 'consent',
        'plural_label' => 'Consents',
        'anonymous' => 'Anonymous',
        'export' => [
            'action' => 'Export CSV',
            'filename' => 'consents',
            'yes' => 'Yes',
            'no' => 'No',
        ],
        'sections' => [
            'visitor' => 'Visitor information',
            'consent' => 'Consent',
        ],
        'fields' => [
            'visitor_id' => 'Visitor ID',
            'user' => 'User',
            'ip_hash' => 'IP hash',
            'user_agent' => 'User agent',
            'preferences' => 'Preferences',
            'config_version' => 'Config version',
            'consent_method' => 'Method',
            'consented_at' => 'Consent date',
            'expires_at' => 'Expires',
        ],
        'filters' => [
            'authenticated' => 'Signed-in user',
            'consented_today' => 'Today',
        ],
    ],

    // Cookie types
    'cookie_type' => [
        'first_party' => 'First party',
        'third_party' => 'Third party',
    ],

    // Banner positions
    'banner_position' => [
        'bottom' => 'Bottom',
        'top' => 'Top',
        'bottom_left' => 'Bottom left',
        'bottom_right' => 'Bottom right',
        'center' => 'Center',
    ],

    // Banner layouts
    'banner_layout' => [
        'bar' => 'Bar',
        'box' => 'Box',
        'modal' => 'Modal',
    ],

    // Banner themes
    'banner_theme' => [
        'auto' => 'Automatic',
        'light' => 'Light',
        'dark' => 'Dark',
        'custom' => 'Custom',
    ],

    // Consent methods
    'consent_method' => [
        'banner' => 'Banner',
        'settings_page' => 'Settings page',
        'api' => 'API',
    ],

    // Consent Mode keys
    'consent_mode' => [
        'ad_storage' => 'Ad storage',
        'ad_storage_description' => 'Allows storage related to advertising',
        'ad_user_data' => 'User data for ads',
        'ad_user_data_description' => 'Allows sending user data to Google for advertising',
        'ad_personalization' => 'Ad personalization',
        'ad_personalization_description' => 'Allows personalized ads',
        'analytics_storage' => 'Analytics storage',
        'analytics_storage_description' => 'Allows storage for usage analytics',
        'functionality_storage' => 'Functionality storage',
        'functionality_storage_description' => 'Allows storage for the site\'s features',
        'personalization_storage' => 'Personalization storage',
        'personalization_storage_description' => 'Allows storage for personalization',
        'security_storage' => 'Security storage',
        'security_storage_description' => 'Allows storage for security features',
    ],

    // Settings
    'settings' => [
        'navigation' => 'Cookie banner',
        'title' => 'Cookie banner settings',
        'saved' => 'Settings saved',

        'appearance' => 'Appearance',
        'banner_position' => 'Banner position',
        'banner_layout' => 'Banner layout',
        'banner_theme' => 'Banner theme',
        'primary_color' => 'Primary color',
        'secondary_color' => 'Secondary color',
        'background_color' => 'Background color',
        'text_color' => 'Text color',

        'texts' => 'Texts',
        'texts_description' => 'Customize the texts shown in the cookie banner',
        'banner_title' => 'Banner title',
        'banner_description' => 'Banner description',
        'accept_all_text' => '"Accept all" text',
        'reject_all_text' => '"Reject all" text',
        'settings_text' => '"Configure" text',
        'save_settings_text' => '"Save preferences" text',
        'privacy_policy_link_text' => 'Privacy policy link text',
        'privacy_policy_url' => 'Privacy policy URL',
        'privacy_policy_url_help' => 'A page of this site (for example, /privacy-policy) or a full address starting with https://',
        'privacy_policy_url_invalid' => 'Enter a path starting with / or an address starting with http:// or https://.',

        'behavior' => 'Behavior',
        'show_reject_all' => 'Show the "Reject all" button',
        'show_settings_button' => 'Show the "Configure" button',
        'show_floating_button' => 'Show the floating preferences button',
        'show_floating_button_help' => 'A small button with a cookie, at the bottom left, opens the cookie preferences once they are chosen. If you add the "Cookie preferences" link to the footer from Navigation, you can remove it.',
        'consent_validity_days' => 'Days the consent is valid',
        'consent_validity_days_help' => 'Number of days the consent is valid before it is asked for again',
        'reconsent_on_change' => 'Ask for consent again when the configuration changes',
        'reconsent_on_change_help' => 'If categories or cookies are added or changed, show the banner again',

        'integrations' => 'Integrations',
        'gtm_enabled' => 'Enable Google Tag Manager',
        'gtm_container_id' => 'GTM container ID',
        'gtm_container_id_invalid' => 'The container ID must look like GTM-XXXXXX.',
        'gtm_category' => 'Google Tag Manager category',
        'gtm_category_help' => 'Without Google Consent Mode, the container does not load until the visitor accepts this category. With Consent Mode it always loads and respects what they accept.',
        'consent_mode_enabled' => 'Enable Google Consent Mode v2',
        'consent_mode_enabled_help' => 'Sends the consent state to Google to meet the regulations',
    ],

    // Permissions
    'permissions' => [
        'categories_view_any' => 'View cookie categories',
        'categories_create' => 'Create cookie categories',
        'categories_update' => 'Edit cookie categories',
        'categories_delete' => 'Delete cookie categories',
        'consents_view_any' => 'View consents',
        'consents_export' => 'Export consents',
    ],

    // Widgets
    'widgets' => [
        'stats' => [
            'title' => 'Consent statistics',
            'total_consents' => 'Total consents',
            'by_method' => 'By method',
        ],
    ],

    // Frontend texts (used in Vue components)
    // Pages offered for the site menus
    'routes' => [
        'preferences' => 'Cookie preferences',
    ],

    'frontend' => [
        'preferences' => 'Cookie preferences',
        'save_error' => 'We could not save your choice. Try again and, if it keeps failing, reload the page.',
        'default_title' => 'We use cookies',
        'default_description' => 'We use our own and third-party cookies to improve your experience and show personalized content.',
        'accept_all' => 'Accept all',
        'reject_all' => 'Reject all',
        'settings' => 'Configure',
        'save_preferences' => 'Save preferences',
        'privacy_policy' => 'Privacy policy',
        'required_category' => 'Required',
        'show_cookies' => 'Show cookies',
        'hide_cookies' => 'Hide cookies',
        'cookie_column' => 'Cookie',
        'provider_column' => 'Provider',
        'duration_column' => 'Duration',
        'cancel' => 'Cancel',
        'close' => 'Close',
    ],

    // Validation messages
    'validation' => [
        'visitor_id_required' => 'The visitor identifier is required.',
        'visitor_id_uuid' => 'The visitor identifier must be a valid UUID.',
        'preferences_required' => 'The cookie preferences are required.',
        'preferences_array' => 'The preferences must be an array.',
        'config_version_required' => 'The configuration version is required.',
    ],
];
