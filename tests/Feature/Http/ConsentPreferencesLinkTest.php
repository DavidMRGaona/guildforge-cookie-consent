<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Http;

use App\Application\Modules\Services\ModuleRouteRegistryInterface;
use App\Application\Services\SettingsServiceInterface;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Withdrawing consent must be as easy as giving it: the visitor reopens the preferences
 * from a footer link or from the floating button.
 */
final class ConsentPreferencesLinkTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    public function test_the_preferences_link_can_be_added_to_the_menus(): void
    {
        $options = app(ModuleRouteRegistryInterface::class)->toRouteOptions();

        $this->assertArrayHasKey('cookie-consent.preferences', $options);
        $this->assertStringContainsString('Preferencias de cookies', $options['cookie-consent.preferences']);
    }

    public function test_opening_the_link_directly_opens_the_preferences_on_the_home_page(): void
    {
        // The banner opens the window without leaving the page; this is for a new tab
        $this->get('/consentimiento-cookies/preferencias')
            ->assertRedirect(url('/').'#preferencias-cookies');
    }

    public function test_the_banner_knows_the_link_and_its_text(): void
    {
        $config = $this->bannerConfig();

        $this->assertSame('/consentimiento-cookies/preferencias', $config['preferencesUrl']);
        $this->assertSame('Preferencias de cookies', $config['texts']['preferences']);
    }

    public function test_the_floating_button_is_shown_unless_turned_off(): void
    {
        $this->assertTrue($this->bannerConfig()['showFloatingButton']);

        app(SettingsServiceInterface::class)->set('cookie-consent.show_floating_button', '0');

        $this->assertFalse($this->bannerConfig()['showFloatingButton']);
    }

    public function test_each_category_says_whether_it_has_active_scripts(): void
    {
        $this->script('analytics', isActive: true);
        $this->script('marketing', isActive: false);

        /** @var list<array{slug: string, hasScripts: bool}> $data */
        $data = $this->get('/consentimiento-cookies/categorias')->assertOk()->json('data');
        $categories = array_column($data, 'hasScripts', 'slug');

        // The banner reloads the page when a choice adds or removes scripts
        $this->assertTrue($categories['analytics']);
        $this->assertFalse($categories['marketing']);
        $this->assertFalse($categories['necessary']);
    }

    /**
     * @return array<string, mixed>
     */
    private function bannerConfig(): array
    {
        /** @var array<string, mixed> */
        return $this->get('/consentimiento-cookies/configuracion')->assertOk()->json('data');
    }

    private function script(string $categorySlug, bool $isActive): void
    {
        CookieScriptModel::create([
            'category_id' => CookieCategoryModel::query()->where('slug', $categorySlug)->value('id'),
            'name' => 'Script of '.$categorySlug,
            'script_head' => '<script>window.loaded = true;</script>',
            'sort_order' => 1,
            'is_active' => $isActive,
        ]);
    }
}
