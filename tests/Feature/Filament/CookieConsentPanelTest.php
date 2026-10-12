<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use App\Filament\Pages\ModuleSettingsPage;
use Illuminate\Support\Str;
use Livewire\Livewire;
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

        $this->registerOnAdminPanel([CookieCategoryResource::class, CookieConsentResource::class]);
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
        Livewire::test(ModuleSettingsPage::class, ['module' => 'cookie-consent'])
            ->assertOk()
            ->fillForm(['banner_theme' => 'custom'])
            ->assertFormFieldIsVisible('primary_color');
    }

    public function test_the_privacy_policy_url_can_be_a_path_of_the_site(): void
    {
        // updateSettings() rewrites the module's config/settings.php: put it back afterwards
        $settingsFile = dirname(__DIR__, 3).'/config/settings.php';
        $original = (string) file_get_contents($settingsFile);

        try {
            Livewire::test(ModuleSettingsPage::class, ['module' => 'cookie-consent'])
                ->assertDontSeeHtml('type="url"')
                ->fillForm(['privacy_policy_url' => '/politica-de-privacidad'])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('/politica-de-privacidad', (require $settingsFile)['privacy_policy_url'] ?? null);
        } finally {
            file_put_contents($settingsFile, $original);
        }
    }

    public function test_the_privacy_policy_url_must_be_a_path_or_a_web_address(): void
    {
        Livewire::test(ModuleSettingsPage::class, ['module' => 'cookie-consent'])
            ->fillForm(['privacy_policy_url' => 'javascript:alert(1)'])
            ->call('save')
            ->assertHasFormErrors(['privacy_policy_url']);
    }

    private function category(): CookieCategoryModel
    {
        return CookieCategoryModel::query()->orderBy('sort_order')->firstOrFail();
    }
}
