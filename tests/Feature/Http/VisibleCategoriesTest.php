<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Http;

use App\Application\Services\SettingsServiceInterface;
use Closure;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The banner, its settings modal and the categories endpoint offer the same categories:
 * the ones that change something for the visitor.
 */
final class VisibleCategoriesTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        CookieModel::query()->delete();
        CookieScriptModel::query()->delete();

        $settings = $this->app->make(SettingsServiceInterface::class);
        $settings->set('cookie-consent.gtm_enabled', '0');

        // Only the preferences category has something behind it
        CookieModel::create([
            'id' => (string) Str::uuid(),
            'category_id' => (string) CookieCategoryModel::query()->where('slug', 'preferences')->value('id'),
            'name' => 'site_preferences',
            'provider' => 'Example',
            'purpose' => 'Remembers a choice',
            'duration' => '90 días',
            'type' => 'first_party',
            'is_active' => true,
        ]);
    }

    public function test_the_banner_only_receives_the_categories_in_use(): void
    {
        $shared = Inertia::getShared('cookieConsent');
        $this->assertInstanceOf(Closure::class, $shared);

        /** @var array{categories: list<array{slug: string}>} $cookieConsent */
        $cookieConsent = $shared();

        $this->assertSame(['necessary', 'preferences'], array_column($cookieConsent['categories'], 'slug'));
    }

    public function test_the_categories_endpoint_only_lists_the_categories_in_use(): void
    {
        $response = $this->getJson(route('cookie-consent.categories'));

        $response->assertOk();
        $this->assertSame(['necessary', 'preferences'], array_column($response->json('data'), 'slug'));
    }
}
