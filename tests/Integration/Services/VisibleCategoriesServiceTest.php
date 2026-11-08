<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Services;

use App\Application\Services\SettingsServiceInterface;
use Illuminate\Support\Str;
use Modules\CookieConsent\Application\DTOs\CategoryDTO;
use Modules\CookieConsent\Application\Services\VisibleCategoriesServiceInterface;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Visitors are only asked about categories that change something: a category with no
 * cookies, no scripts, no Google Tag Manager container and no Consent Mode signal that
 * something reads is left out of the banner.
 */
final class VisibleCategoriesServiceTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    private const string CONTAINER = 'GTM-TEST123';

    protected function setUp(): void
    {
        parent::setUp();

        // Start from categories with nothing behind them
        CookieModel::query()->delete();
        CookieScriptModel::query()->delete();
        CookieCategoryModel::query()->delete();

        $this->category('necessary', isRequired: true);
        $this->category('preferences', consentModeKeys: ['personalization_storage']);
        $this->category('analytics', consentModeKeys: ['analytics_storage']);
        $this->category('marketing', consentModeKeys: ['ad_storage']);

        $this->setting('gtm_enabled', '0');
        $this->setting('gtm_container_id', '');
        $this->setting('gtm_category', 'analytics');
        $this->setting('consent_mode_enabled', '1');
    }

    public function test_an_empty_optional_category_is_not_shown(): void
    {
        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_a_required_category_is_always_shown(): void
    {
        $this->category('security', isRequired: true);

        $this->assertSame(['necessary', 'security'], $this->visibleSlugs());
    }

    public function test_an_active_cookie_shows_its_category(): void
    {
        $this->cookie('preferences');

        $this->assertSame(['necessary', 'preferences'], $this->visibleSlugs());
    }

    public function test_an_inactive_cookie_does_not_show_its_category(): void
    {
        $this->cookie('preferences', isActive: false);

        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_an_active_script_shows_its_category(): void
    {
        $this->setting('consent_mode_enabled', '0');
        $this->script('marketing');

        $this->assertSame(['necessary', 'marketing'], $this->visibleSlugs());
    }

    public function test_an_inactive_script_does_not_show_its_category(): void
    {
        $this->script('marketing', isActive: false);

        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_the_category_of_the_tag_manager_container_is_shown(): void
    {
        $this->setting('consent_mode_enabled', '0');
        $this->enableTagManager('analytics');

        $this->assertSame(['necessary', 'analytics'], $this->visibleSlugs());
    }

    public function test_the_tag_manager_category_is_not_shown_while_the_container_is_off(): void
    {
        $this->setting('consent_mode_enabled', '0');
        $this->setting('gtm_container_id', self::CONTAINER);
        $this->setting('gtm_category', 'analytics');

        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_the_tag_manager_category_is_not_shown_with_a_malformed_container(): void
    {
        $this->setting('consent_mode_enabled', '0');
        $this->setting('gtm_enabled', '1');
        $this->setting('gtm_container_id', 'not-a-container');

        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_consent_mode_signals_show_their_categories_when_the_container_reads_them(): void
    {
        $this->enableTagManager('marketing');

        $this->assertSame(['necessary', 'preferences', 'analytics', 'marketing'], $this->visibleSlugs());
    }

    public function test_consent_mode_signals_show_their_categories_when_a_script_may_read_them(): void
    {
        // A Google tag can live in any category, the required one included
        $this->script('necessary');

        $this->assertSame(['necessary', 'preferences', 'analytics', 'marketing'], $this->visibleSlugs());
    }

    public function test_consent_mode_signals_alone_do_not_show_a_category_when_nothing_reads_them(): void
    {
        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_consent_mode_keys_do_not_count_while_consent_mode_is_off(): void
    {
        $this->setting('consent_mode_enabled', '0');
        $this->script('necessary');

        $this->assertSame(['necessary'], $this->visibleSlugs());
    }

    public function test_categories_keep_their_order_and_their_cookies(): void
    {
        $this->cookie('marketing');
        $this->cookie('preferences');

        $categories = $this->app->make(VisibleCategoriesServiceInterface::class)->getVisibleCategories();

        $this->assertSame(['necessary', 'preferences', 'marketing'], array_map(fn (CategoryDTO $category): string => $category->slug, $categories));
        $this->assertCount(1, $categories[1]->cookies);
    }

    /**
     * @return list<string>
     */
    private function visibleSlugs(): array
    {
        return array_map(
            fn (CategoryDTO $category): string => $category->slug,
            $this->app->make(VisibleCategoriesServiceInterface::class)->getVisibleCategories(),
        );
    }

    /**
     * @param  list<string>  $consentModeKeys
     */
    private function category(string $slug, bool $isRequired = false, array $consentModeKeys = []): void
    {
        CookieCategoryModel::create([
            'id' => (string) Str::uuid(),
            'name' => "Category {$slug}",
            'slug' => $slug,
            'description' => 'Test category',
            'is_required' => $isRequired,
            'is_system' => false,
            'sort_order' => CookieCategoryModel::query()->count() + 1,
            'consent_mode_keys' => $consentModeKeys,
        ]);
    }

    private function cookie(string $categorySlug, bool $isActive = true): void
    {
        CookieModel::create([
            'id' => (string) Str::uuid(),
            'category_id' => $this->categoryId($categorySlug),
            'name' => "cookie_{$categorySlug}",
            'provider' => 'Example',
            'purpose' => 'Test cookie',
            'duration' => '1 año',
            'type' => 'first_party',
            'is_active' => $isActive,
        ]);
    }

    private function script(string $categorySlug, bool $isActive = true): void
    {
        CookieScriptModel::create([
            'id' => (string) Str::uuid(),
            'category_id' => $this->categoryId($categorySlug),
            'name' => "Script {$categorySlug}",
            'script_head' => '<script>window.example = true;</script>',
            'sort_order' => 1,
            'is_active' => $isActive,
        ]);
    }

    private function enableTagManager(string $categorySlug): void
    {
        $this->setting('gtm_enabled', '1');
        $this->setting('gtm_container_id', self::CONTAINER);
        $this->setting('gtm_category', $categorySlug);
    }

    private function categoryId(string $slug): string
    {
        return (string) CookieCategoryModel::query()->where('slug', $slug)->value('id');
    }

    private function setting(string $key, string $value): void
    {
        $this->app->make(SettingsServiceInterface::class)->set("cookie-consent.{$key}", $value);
    }
}
