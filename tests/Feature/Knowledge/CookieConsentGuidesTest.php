<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Knowledge;

use App\Application\Knowledge\Services\KnowledgeQueryServiceInterface;
use App\Domain\Knowledge\Enums\GuideAudience;
use App\Domain\Knowledge\ValueObjects\GuideId;
use App\Filament\Pages\KnowledgeBasePage;
use App\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Filament\Pages\CookieConsentSettings;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\CreateCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\EditCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\ListCookieCategories;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ViewCookieConsent;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Who sees each of the module's guides, and the help button on its panel pages.
 */
final class CookieConsentGuidesTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    /** Every account meets the banner, so its guides are for everyone. */
    private const array SITE_GUIDES = [
        'cookie-consent.change-cookie-choice',
        'cookie-consent.cookie-preferences',
    ];

    private const array PANEL_GUIDES = [
        'cookie-consent.panel.banner-settings',
        'cookie-consent.panel.categories',
        'cookie-consent.panel.consents',
        'cookie-consent.panel.cookies',
        'cookie-consent.panel.overview',
        'cookie-consent.panel.scripts',
    ];

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel(
            [CookieCategoryResource::class, CookieConsentResource::class],
            [CookieConsentSettings::class],
        );
        $this->artisanCommand('knowledge:sync')->assertSuccessful()->run();
    }

    public function test_an_admin_sees_every_panel_guide_rendered_without_raw_markup(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $this->assertSame(self::PANEL_GUIDES, $this->visibleGuides($admin, GuideAudience::Panel));
        foreach (self::PANEL_GUIDES as $id) {
            $this->assertRenderedWithoutRawMarkup($admin, GuideAudience::Panel, $id);
        }
    }

    public function test_each_permission_shows_only_its_panel_guides(): void
    {
        $this->assertSame(
            ['cookie-consent.panel.categories', 'cookie-consent.panel.overview'],
            $this->visibleGuides($this->userWith('cookie-consent:cookie_categories.view_any'), GuideAudience::Panel),
        );
        // Cookies and scripts are added on the category's edit page
        $this->assertSame(
            ['cookie-consent.panel.cookies', 'cookie-consent.panel.scripts'],
            $this->visibleGuides($this->userWith('cookie-consent:cookie_categories.update'), GuideAudience::Panel),
        );
        $this->assertSame(
            ['cookie-consent.panel.consents', 'cookie-consent.panel.overview'],
            $this->visibleGuides($this->userWith('cookie-consent:cookie_consents.view_any'), GuideAudience::Panel),
        );
        // The banner settings page asks for the core's settings permission
        $this->assertSame(
            ['cookie-consent.panel.banner-settings', 'cookie-consent.panel.overview'],
            $this->visibleGuides($this->userWith('settings.manage'), GuideAudience::Panel),
        );
        $this->assertSame([], $this->visibleGuides(UserModel::factory()->editor()->create(), GuideAudience::Panel));
    }

    public function test_every_account_sees_the_site_guides_rendered_without_raw_markup(): void
    {
        $member = UserModel::factory()->create();

        $this->assertSame(self::SITE_GUIDES, $this->visibleGuides($member, GuideAudience::Site));
        foreach (self::SITE_GUIDES as $id) {
            $this->assertRenderedWithoutRawMarkup($member, GuideAudience::Site, $id);
        }
    }

    public function test_the_panel_pages_open_their_guide(): void
    {
        $this->actingAsPanelAdmin();
        $category = ['record' => CookieCategoryModel::query()->orderBy('sort_order')->firstOrFail()->getRouteKey()];
        $consent = ['record' => $this->consent()->getRouteKey()];

        $pages = [
            [ListCookieCategories::class, [], 'cookie-consent.panel.categories'],
            [CreateCookieCategory::class, [], 'cookie-consent.panel.categories'],
            [EditCookieCategory::class, $category, 'cookie-consent.panel.categories'],
            [ListCookieConsents::class, [], 'cookie-consent.panel.consents'],
            [ViewCookieConsent::class, $consent, 'cookie-consent.panel.consents'],
            [CookieConsentSettings::class, [], 'cookie-consent.panel.banner-settings'],
        ];

        foreach ($pages as [$page, $parameters, $guide]) {
            Livewire::test($page, $parameters)
                ->assertActionVisible('contextualHelp')
                ->assertActionHasUrl('contextualHelp', KnowledgeBasePage::getUrl(['guia' => $guide]));
        }
    }

    public function test_a_person_who_only_reads_the_categories_gets_their_help_button(): void
    {
        // The pages ask for no more than their guide does
        $this->actingAs($this->userWith('cookie-consent:cookie_categories.view_any'));

        Livewire::test(ListCookieCategories::class)
            ->assertActionVisible('contextualHelp')
            ->assertActionHasUrl('contextualHelp', KnowledgeBasePage::getUrl(['guia' => 'cookie-consent.panel.categories']));
    }

    private function assertRenderedWithoutRawMarkup(UserModel $user, GuideAudience $audience, string $id): void
    {
        $guide = $this->queries()->guide($user, $audience, GuideId::fromString($id));
        $this->assertNotNull($guide, $id);
        $this->assertStringNotContainsString('[[', $guide->html, $id);
        $this->assertStringNotContainsString('{{', $guide->html, $id);
        $this->assertStringNotContainsString('[!', $guide->html, $id);
    }

    private function consent(): CookieConsentModel
    {
        return CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => '6f1c2a3b-4d5e-4f60-8a71-92b3c4d5e6f7',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => ['necessary' => true, 'analytics' => false],
            'config_version' => 1,
            'consent_method' => 'banner',
            'consented_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function visibleGuides(UserModel $user, GuideAudience $audience): array
    {
        $ids = array_values(array_filter(
            $this->queries()->visibleIds($user, $audience),
            static fn (string $id): bool => str_starts_with($id, 'cookie-consent.'),
        ));
        sort($ids);

        return $ids;
    }

    private function userWith(string $permissionKey): UserModel
    {
        $role = RoleModel::query()->create(['name' => 'tester_'.uniqid(), 'display_name' => 'Permissions tester']);
        $permission = PermissionModel::query()->firstOrCreate(
            ['key' => $permissionKey],
            ['label' => $permissionKey, 'resource' => 'cookie-consent', 'action' => 'test', 'module' => 'cookie-consent'],
        );
        $role->permissions()->attach($permission->id);
        $user = UserModel::factory()->editor()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    private function queries(): KnowledgeQueryServiceInterface
    {
        return $this->app->make(KnowledgeQueryServiceInterface::class);
    }
}
