<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use App\Application\Services\SettingsServiceInterface;
use App\Filament\Pages\ModuleSettingsPage;
use App\Filament\Pages\ModulesPage;
use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Filament\Pages\CookieConsentSettings;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\CreateCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\EditCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\ListCookieCategories;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\CookiesRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\ScriptsRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ViewCookieConsent;
use Modules\CookieConsent\Filament\Widgets\ConsentStatsWidget;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class CookieConsentPanelTest extends ModuleTestCase
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

    public function test_category_pages_render(): void
    {
        $category = $this->category();

        Livewire::test(ListCookieCategories::class)->assertOk()->assertCanSeeTableRecords([$category]);
        Livewire::test(CreateCookieCategory::class)->assertOk();
        Livewire::test(EditCookieCategory::class, ['record' => $category->getRouteKey()])
            ->assertOk()
            ->assertFormSet(['name' => $category->name]);
    }

    public function test_category_relation_managers_render(): void
    {
        $category = $this->category();

        foreach ([CookiesRelationManager::class, ScriptsRelationManager::class] as $relationManager) {
            Livewire::test($relationManager, ['ownerRecord' => $category, 'pageClass' => EditCookieCategory::class])
                ->assertOk();
        }
    }

    public function test_consent_pages_render(): void
    {
        $consent = CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => 'visitor-1',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => ['analytics' => true],
            'config_version' => 1,
            'consent_method' => 'banner',
            'consented_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        Livewire::test(ListCookieConsents::class)->assertOk()->assertCanSeeTableRecords([$consent]);
        Livewire::test(ViewCookieConsent::class, ['record' => $consent->getRouteKey()])
            ->assertOk()
            ->assertSee('visitor-1');
    }

    public function test_consents_cannot_be_created_edited_or_deleted(): void
    {
        $consent = CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => 'visitor-1',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => ['analytics' => true],
            'config_version' => 1,
            'consent_method' => 'banner',
            'consented_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertFalse(CookieConsentResource::canCreate());
        $this->assertFalse(CookieConsentResource::canEdit($consent));
        $this->assertFalse(CookieConsentResource::canDelete($consent));
    }

    public function test_stats_widget_renders(): void
    {
        Livewire::test(ConsentStatsWidget::class)->assertOk();
    }

    public function test_custom_banner_theme_shows_the_colour_pickers(): void
    {
        Livewire::test(CookieConsentSettings::class)
            ->assertOk()
            ->fillForm(['banner_theme' => 'custom'])
            ->assertFormFieldIsVisible('primary_color');
    }

    public function test_the_privacy_policy_url_can_be_a_path_of_the_site(): void
    {
        Livewire::test(CookieConsentSettings::class)
            ->assertDontSeeHtml('type="url"')
            ->fillForm(['privacy_policy_url' => '/politica-de-privacidad'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    #[DataProvider('notAPathOrAWebAddress')]
    public function test_the_privacy_policy_url_must_be_a_path_or_a_web_address(string $url): void
    {
        Livewire::test(CookieConsentSettings::class)
            ->fillForm(['privacy_policy_url' => $url])
            ->call('save')
            ->assertHasFormErrors(['privacy_policy_url']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAPathOrAWebAddress(): array
    {
        return [
            'script' => ['javascript:alert(1)'],
            // Browsers read "/\host" as "//host": another site
            'backslash after the slash' => ['/\\evil.example'],
        ];
    }

    public function test_saved_settings_reach_the_banner_without_touching_the_module_files(): void
    {
        $settingsFile = dirname(__DIR__, 3).'/config/settings.php';
        $checksum = md5_file($settingsFile);
        $settings = app(SettingsServiceInterface::class);
        $settings->set('cookie-consent.privacy_policy_url', 'https://old.example/privacy');
        $settings->set('cookie-consent.show_reject_all', '1');
        $settings->set('cookie-consent.config_version', '7');

        Livewire::test(CookieConsentSettings::class)
            ->fillForm([
                'privacy_policy_url' => '/politica-de-privacidad',
                'banner_theme' => 'custom',
                'primary_color' => '#123456',
                'show_reject_all' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $config = app(BannerConfigServiceInterface::class)->getBannerConfig();
        $this->assertSame('/politica-de-privacidad', $config->texts['privacyUrl']);
        $this->assertSame('custom', $config->theme);
        $this->assertSame('#123456', $config->colors['primary']);
        $this->assertFalse($config->showRejectAll);
        $this->assertSame('0', $settings->get('cookie-consent.show_reject_all'));
        $this->assertSame('7', $settings->get('cookie-consent.config_version'));
        $this->assertSame($checksum, md5_file($settingsFile));
    }

    public function test_the_settings_page_shows_the_stored_settings_or_the_module_defaults(): void
    {
        $settings = app(SettingsServiceInterface::class);
        $settings->set('cookie-consent.banner_title', 'Cookies en la guarida');
        $settings->set('cookie-consent.show_logo', '0');
        SettingModel::query()->where('key', 'cookie-consent.primary_color')->delete();
        $settings->clearCache();

        Livewire::test(CookieConsentSettings::class)
            ->assertOk()
            ->assertFormSet([
                'banner_title' => 'Cookies en la guarida',
                'show_logo' => false,
                'primary_color' => '#10B981',
            ]);
    }

    public function test_the_generic_module_settings_page_no_longer_edits_the_banner(): void
    {
        $bannerSettings = fn (): array => SettingModel::query()
            ->where('key', 'like', 'cookie-consent.%')
            ->orderBy('key')
            ->pluck('value', 'key')
            ->all();
        $before = $bannerSettings();

        $response = $this->get(ModuleSettingsPage::getUrl(['module' => 'cookie-consent']));

        // Core 3.0.1 shows the page without a form; newer cores send a module without a schema back to the module list
        if ($response->isRedirect()) {
            $response->assertRedirect(ModulesPage::getUrl());
        } else {
            $response->assertOk()
                ->assertSee(__('modules.filament.settings_page.no_settings'))
                ->assertDontSee('wire:submit="save"', false);
        }

        $this->assertSame($before, $bannerSettings());
    }

    private function category(): CookieCategoryModel
    {
        return CookieCategoryModel::query()->orderBy('sort_order')->firstOrFail();
    }
}
