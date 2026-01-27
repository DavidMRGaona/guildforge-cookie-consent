<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Ramsey\Uuid\Uuid;

final class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Cookies necesarias',
                'slug' => 'necessary',
                'description' => 'Cookies esenciales para el funcionamiento del sitio. No pueden desactivarse.',
                'is_required' => true,
                'is_system' => true,
                'sort_order' => 1,
                'consent_mode_keys' => ['security_storage', 'functionality_storage'],
            ],
            [
                'name' => 'Cookies de preferencias',
                'slug' => 'preferences',
                'description' => 'Permiten recordar tus preferencias y personalización.',
                'is_required' => false,
                'is_system' => true,
                'sort_order' => 2,
                'consent_mode_keys' => ['personalization_storage'],
            ],
            [
                'name' => 'Cookies analíticas',
                'slug' => 'analytics',
                'description' => 'Nos ayudan a entender cómo usas el sitio web.',
                'is_required' => false,
                'is_system' => true,
                'sort_order' => 3,
                'consent_mode_keys' => ['analytics_storage'],
            ],
            [
                'name' => 'Cookies de marketing',
                'slug' => 'marketing',
                'description' => 'Se utilizan para mostrarte anuncios relevantes.',
                'is_required' => false,
                'is_system' => true,
                'sort_order' => 4,
                'consent_mode_keys' => ['ad_storage', 'ad_user_data', 'ad_personalization'],
            ],
        ];

        foreach ($categories as $category) {
            CookieCategoryModel::firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_required' => $category['is_required'],
                    'is_system' => $category['is_system'],
                    'sort_order' => $category['sort_order'],
                    'consent_mode_keys' => $category['consent_mode_keys'],
                ]
            );
        }
    }
}
