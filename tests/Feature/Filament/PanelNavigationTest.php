<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use App\Filament\Clusters\SettingsCluster;
use App\Filament\Support\PanelNavigationGroup;
use Modules\CookieConsent\Filament\Pages\CookieConsentSettings;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The panel menu is grouped by task (core#409): cookies live in «Sitio web» with the consent
 * records under them, and the banner settings join the «Configuración» cluster.
 */
final class PanelNavigationTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel(
            [CookieCategoryResource::class, CookieConsentResource::class],
            [CookieConsentSettings::class],
        );
        $this->actingAsPanelAdmin();
    }

    public function test_cookies_sit_in_the_website_group_in_their_own_slot(): void
    {
        $this->assertSame(PanelNavigationGroup::Site->getLabel(), CookieCategoryResource::getNavigationGroup());
        $this->assertSame(60, CookieCategoryResource::getNavigationSort());
        $this->assertSame('Cookies', CookieCategoryResource::getNavigationLabel());
        $this->assertNull(CookieCategoryResource::getNavigationParentItem());
    }

    public function test_consents_are_a_child_of_cookies_in_the_same_group(): void
    {
        $this->assertSame(PanelNavigationGroup::Site->getLabel(), CookieConsentResource::getNavigationGroup());
        $this->assertSame(CookieCategoryResource::getNavigationLabel(), CookieConsentResource::getNavigationParentItem());
        $this->assertSame('Consentimientos', CookieConsentResource::getNavigationLabel());
    }

    public function test_banner_settings_join_the_settings_cluster(): void
    {
        $this->assertSame(SettingsCluster::class, CookieConsentSettings::getCluster());
        $this->assertSame(110, CookieConsentSettings::getNavigationSort());
        $this->assertSame('Banner de cookies', CookieConsentSettings::getNavigationLabel());
        $this->assertNull(CookieConsentSettings::getNavigationGroup());
        $this->assertSame('Sistema', SettingsCluster::getNavigationGroup());
        $this->assertStringStartsWith('/admin/configuracion/', (string) parse_url(CookieConsentSettings::getUrl(), PHP_URL_PATH));
    }

    public function test_the_panel_menu_shows_cookies_under_the_website_group(): void
    {
        $this->get(CookieCategoryResource::getUrl())
            ->assertOk()
            ->assertSeeInOrder([PanelNavigationGroup::Site->getLabel(), 'Cookies']);
    }
}
