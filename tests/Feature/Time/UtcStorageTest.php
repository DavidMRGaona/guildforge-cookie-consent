<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Time;

use App\Application\Services\SettingsServiceInterface;
use App\Application\Services\TimezoneServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Concerns\StoresInstantsInStorageTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Application\DTOs\SaveConsentDTO;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ViewCookieConsent;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;
use Tests\Support\Time\UsesUtcStorage;

/**
 * With UTC storage the instants in the database are UTC, but the panel, the JSON and the
 * validity of a consent follow the site's Madrid calendar.
 */
final class UtcStorageTest extends ModuleTestCase
{
    use InteractsWithModulePanel;
    use UsesUtcStorage;

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    private const string ANALYTICS_SCRIPT = '<script>window.analyticsTestLoaded = true;</script>';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([CookieCategoryResource::class, CookieConsentResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_every_model_stores_instants_in_the_storage_timezone(): void
    {
        $files = glob(dirname(__DIR__, 3).'/src/Infrastructure/Persistence/Eloquent/Models/*.php') ?: [];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $class = 'Modules\\CookieConsent\\Infrastructure\\Persistence\\Eloquent\\Models\\'.basename($file, '.php');

            $this->assertContains(StoresInstantsInStorageTimezone::class, class_uses_recursive($class), $class);
        }
    }

    public function test_the_today_filter_follows_the_madrid_day_with_utc_storage(): void
    {
        $this->useUtcStorage();

        $this->assertTodayFilterBoundary();
    }

    public function test_the_today_filter_follows_the_madrid_day_with_madrid_storage(): void
    {
        $this->skipUnlessMadridStorage();

        $this->assertTodayFilterBoundary();
    }

    public function test_the_consent_dates_are_stored_in_utc_and_shown_in_madrid_time(): void
    {
        $this->useUtcStorage();
        $consent = $this->consent('2026-07-01T10:00:00Z', '2027-07-01T10:00:00Z');

        $this->assertSame('2026-07-01 10:00:00', substr((string) DB::table('cookie_consent_consents')->where('id', $consent->id)->value('consented_at'), 0, 19));

        Livewire::test(ListCookieConsents::class)
            ->assertTableColumnFormattedStateSet('consented_at', '01/07/2026 12:00', $consent);

        Livewire::test(ViewCookieConsent::class, ['record' => $consent->getRouteKey()])
            ->assertSee('01/07/2026 12:00:00');
    }

    public function test_the_consent_is_serialized_in_the_display_zone(): void
    {
        $this->useUtcStorage();
        $this->travelTo(CarbonImmutable::parse('2026-07-01T10:00:00Z'));
        $category = CookieCategoryModel::query()->where('slug', 'necessary')->firstOrFail();

        $consent = app(ConsentServiceInterface::class)->saveConsent(new SaveConsentDTO(
            visitorId: '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
            preferences: [$category->slug => true],
            configVersion: 1,
            consentMethod: ConsentMethod::Banner,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        $this->assertSame('2026-07-01T12:00:00+02:00', $consent->consentedAt);
    }

    public function test_the_validity_counts_calendar_days_in_the_display_zone(): void
    {
        $this->useUtcStorage();

        $this->assertServiceValidityBoundary();
    }

    public function test_the_validity_counts_calendar_days_in_the_display_zone_with_madrid_storage(): void
    {
        $this->skipUnlessMadridStorage();

        $this->assertServiceValidityBoundary();
    }

    public function test_the_cookie_validity_counts_calendar_days_in_the_display_zone(): void
    {
        $this->useUtcStorage();

        $this->assertCookieValidityBoundary();
    }

    public function test_the_cookie_validity_counts_calendar_days_in_the_display_zone_with_madrid_storage(): void
    {
        $this->skipUnlessMadridStorage();

        $this->assertCookieValidityBoundary();
    }

    private function skipUnlessMadridStorage(): void
    {
        if (app(TimezoneServiceInterface::class)->storageTimezone()->getName() !== 'Europe/Madrid') {
            $this->markTestSkipped('Only meaningful with Madrid storage; the UTC test covers the other.');
        }
    }

    private function assertServiceValidityBoundary(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.consent_validity_days', '2');
        // 12:00 in Madrid on 24 October; the clocks go back on the night of the 25th
        $this->travelTo(CarbonImmutable::parse('2026-10-24T10:00:00Z'));
        $category = CookieCategoryModel::query()->where('slug', 'necessary')->firstOrFail();

        app(ConsentServiceInterface::class)->saveConsent(new SaveConsentDTO(
            visitorId: '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
            preferences: [$category->slug => true],
            configVersion: 1,
            consentMethod: ConsentMethod::Banner,
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        ));

        // Two calendar days later is 12:00 on the 26th, which is 11:00Z (49 hours elapsed)
        $expiresAt = CarbonImmutable::parse((string) DB::table('cookie_consent_consents')->value('expires_at'), app(TimezoneServiceInterface::class)->storageTimezone());
        $this->assertSame('2026-10-26T11:00:00+00:00', $expiresAt->utc()->toIso8601String());
        $this->assertSame('2026-10-26 12:00:00', app(TimezoneServiceInterface::class)->toDisplayWallTime($expiresAt, 'Y-m-d H:i:s'));
    }

    private function assertCookieValidityBoundary(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.consent_validity_days', '2');
        Route::middleware('web')->get('/cookie-consent-test-page', fn () => response(
            '<html><head><title>Test</title></head><body><p>Contenido</p></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        ));
        CookieScriptModel::create([
            'category_id' => CookieCategoryModel::query()->where('slug', 'analytics')->value('id'),
            'name' => 'Analytics test',
            'script_head' => self::ANALYTICS_SCRIPT,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $cookie = (string) json_encode([
            'visitorId' => '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
            'version' => 1,
            'timestamp' => '2026-10-24T12:00:00+02:00',
            'preferences' => ['necessary' => true, 'analytics' => true, 'marketing' => false],
        ]);
        $page = fn (): string => (string) $this->withUnencryptedCookie('guildforge_cookie_consent', $cookie)
            ->get('/cookie-consent-test-page')
            ->getContent();

        // The consent expires on 26 October at 12:00 in Madrid (11:00Z), 49 hours later
        $this->travelTo(CarbonImmutable::parse('2026-10-26T10:30:00Z'));
        $this->assertStringContainsString(self::ANALYTICS_SCRIPT, $page());

        $this->travelTo(CarbonImmutable::parse('2026-10-26T11:30:00Z'));
        $this->assertStringNotContainsString(self::ANALYTICS_SCRIPT, $page());
    }

    private function assertTodayFilterBoundary(): void
    {
        // 00:30 on 25 October in Madrid: the day began at 22:00Z the day before
        $this->travelTo(CarbonImmutable::parse('2026-10-24T22:30:00Z'));
        $inside = $this->consent('2026-10-24T22:10:00Z', '2027-10-24T22:10:00Z', 'inside');
        $outside = $this->consent('2026-10-24T21:50:00Z', '2027-10-24T21:50:00Z', 'outside');

        Livewire::test(ListCookieConsents::class)
            ->filterTable('consented_today')
            ->assertCanSeeTableRecords([$inside])
            ->assertCanNotSeeTableRecords([$outside]);
    }

    private function consent(string $consentedAt, string $expiresAt, string $visitor = 'visitor-1'): CookieConsentModel
    {
        return CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => $visitor,
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => ['necessary' => true],
            'config_version' => 1,
            'consent_method' => 'banner',
            'consented_at' => CarbonImmutable::parse($consentedAt),
            'expires_at' => CarbonImmutable::parse($expiresAt),
        ])->refresh();
    }
}
