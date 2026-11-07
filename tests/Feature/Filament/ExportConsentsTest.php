<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use App\Application\Services\TimezoneServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;
use Modules\CookieConsent\Infrastructure\Export\ConsentCsvExport;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class ExportConsentsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    private const string VIEW_ANY = 'cookie-consent:cookie_consents.view_any';

    private const string EXPORT = 'cookie-consent:cookie_consents.export';

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([CookieConsentResource::class]);
        CookieCategoryModel::query()->delete();
        $this->category('necessary', 'Necessary', 1);
        $this->category('analytics', 'Analytics', 2);
    }

    public function test_the_export_action_needs_the_export_permission(): void
    {
        $this->actingAs($this->userWith([self::VIEW_ANY, self::EXPORT]));
        Livewire::test(ListCookieConsents::class)->assertActionVisible('export');

        $this->actingAs($this->userWith([self::VIEW_ANY]));
        Livewire::test(ListCookieConsents::class)->assertActionHidden('export');
    }

    public function test_someone_without_the_permission_downloads_nothing(): void
    {
        $this->consent('11111111-1111-4111-8111-111111111111');
        $this->actingAs($this->userWith([self::VIEW_ANY]));

        // Without the button, the way a forged request would call the action
        $component = Livewire::test(ListCookieConsents::class)
            ->call('mountAction', 'export')
            ->call('callMountedAction');

        $this->assertNull($this->downloadEffect($component));
    }

    public function test_it_downloads_a_csv_file_named_after_the_day(): void
    {
        $this->consent('11111111-1111-4111-8111-111111111111');
        $this->actingAs($this->userWith([self::VIEW_ANY, self::EXPORT]));
        $day = app(TimezoneServiceInterface::class)->today()->format('Y-m-d');

        Livewire::test(ListCookieConsents::class)
            ->callAction('export')
            ->assertFileDownloaded("consentimientos-{$day}.csv");
    }

    public function test_it_exports_what_the_filters_leave_on_the_list(): void
    {
        $this->consent('11111111-1111-4111-8111-111111111111');
        $this->consent('22222222-2222-4222-8222-222222222222', user: UserModel::factory()->create(['name' => 'Ana Pérez']));
        $this->actingAs($this->userWith([self::VIEW_ANY, self::EXPORT]));

        $component = Livewire::test(ListCookieConsents::class)
            ->filterTable('authenticated')
            ->callAction('export');
        $csv = $this->downloadedCsv($component);

        $this->assertStringContainsString('22222222-2222-4222-8222-222222222222', $csv);
        $this->assertStringNotContainsString('11111111-1111-4111-8111-111111111111', $csv);
    }

    public function test_the_file_has_the_chosen_columns_and_nothing_more(): void
    {
        $this->consent(
            '11111111-1111-4111-8111-111111111111',
            preferences: ['necessary' => true, 'analytics' => false],
            user: UserModel::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana@example.com']),
        );

        $lines = $this->csvLines();

        $this->assertStringStartsWith("\u{FEFF}", $lines[0]);
        $this->assertSame(
            ['ID visitante', 'Usuario', 'Método', 'Versión config', 'Necessary', 'Analytics', 'Fecha consentimiento', 'Expira'],
            str_getcsv(ltrim($lines[0], "\u{FEFF}"), ';', escape: ''),
        );
        $row = str_getcsv($lines[1], ';', escape: '');
        $this->assertSame('11111111-1111-4111-8111-111111111111', $row[0]);
        $this->assertSame('Ana Pérez', $row[1]);
        $this->assertSame('Banner', $row[2]);
        $this->assertSame('3', $row[3]);
        $this->assertSame(['Sí', 'No'], [$row[4], $row[5]]);
        $this->assertCount(8, $row);

        $csv = implode("\n", $lines);
        $this->assertStringNotContainsString(hash('sha256', '127.0.0.1'), $csv);
        $this->assertStringNotContainsString('PHPUnit', $csv);
        $this->assertStringNotContainsString('ana@example.com', $csv);
    }

    public function test_a_visitor_without_a_user_has_that_cell_empty_and_a_missing_category_stays_blank(): void
    {
        $this->consent('11111111-1111-4111-8111-111111111111', preferences: ['necessary' => true]);

        $row = str_getcsv($this->csvLines()[1], ';', escape: '');

        $this->assertSame('', $row[1]);
        $this->assertSame(['Sí', ''], [$row[4], $row[5]]);
    }

    public function test_values_a_spreadsheet_would_run_as_a_formula_are_neutralised(): void
    {
        $this->consent(
            '11111111-1111-4111-8111-111111111111',
            user: UserModel::factory()->create(['name' => '=HYPERLINK("http://example.com")']),
        );

        $row = str_getcsv($this->csvLines()[1], ';', escape: '');

        $this->assertSame('\'=HYPERLINK("http://example.com")', $row[1]);
    }

    /**
     * @return list<string>
     */
    private function csvLines(): array
    {
        $output = fopen('php://memory', 'w+b');
        $this->assertNotFalse($output);
        app(ConsentCsvExport::class)->write(CookieConsentModel::query()->orderBy('consented_at'), $output);
        rewind($output);

        return array_values(array_filter(explode("\n", trim((string) stream_get_contents($output)))));
    }

    private function downloadedCsv(mixed $component): string
    {
        $download = $this->downloadEffect($component);
        $this->assertNotNull($download, 'Nothing was downloaded');

        return (string) base64_decode($download['content'], true);
    }

    /**
     * @return array{content: string}|null
     */
    private function downloadEffect(mixed $component): ?array
    {
        /** @var array{content: string}|null $download */
        $download = $component->effects['download'] ?? null;

        return $download;
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    private function consent(string $visitorId, array $preferences = ['necessary' => true], ?UserModel $user = null): CookieConsentModel
    {
        return CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => $visitorId,
            'user_id' => $user?->getKey(),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => $preferences,
            'config_version' => 3,
            'consent_method' => 'banner',
            'consented_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    private function category(string $slug, string $name, int $sortOrder): CookieCategoryModel
    {
        return CookieCategoryModel::create([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => $slug,
            'description' => 'Test category',
            'is_required' => false,
            'is_system' => false,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function userWith(array $permissionKeys): UserModel
    {
        $role = RoleModel::query()->create(['name' => 'tester_'.uniqid(), 'display_name' => 'Permissions tester']);

        foreach ($permissionKeys as $key) {
            $permission = PermissionModel::query()->firstOrCreate(
                ['key' => $key],
                ['label' => $key, 'resource' => 'cookie-consent', 'action' => Str::afterLast($key, '.'), 'module' => 'cookie-consent'],
            );
            $role->permissions()->attach($permission->id);
        }

        $user = UserModel::factory()->editor()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
