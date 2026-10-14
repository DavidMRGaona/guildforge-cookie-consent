<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Http;

use App\Application\Services\SettingsServiceInterface;
use Illuminate\Support\Facades\Route;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The banner writes the consent cookie from JavaScript, unencrypted: the web group
 * must hand it to InjectCookieScripts as it is.
 */
final class InjectCookieScriptsTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    private const ANALYTICS_SCRIPT = '<script>window.analyticsTestLoaded = true;</script>';

    private const ACCEPTS_ANALYTICS = ['necessary' => true, 'analytics' => true, 'marketing' => false];

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_scripts_of_the_accepted_categories_are_injected(): void
    {
        $response = $this->withUnencryptedCookie('guildforge_cookie_consent', $this->consentCookie([
            'necessary' => true,
            'analytics' => true,
            'marketing' => false,
        ]))->get('/cookie-consent-test-page');

        $response->assertOk();
        $this->assertStringContainsString(self::ANALYTICS_SCRIPT, (string) $response->getContent());
    }

    public function test_the_stored_consent_is_restored_in_consent_mode(): void
    {
        $response = $this->withUnencryptedCookie('guildforge_cookie_consent', $this->consentCookie([
            'necessary' => true,
            'analytics' => true,
            'marketing' => false,
        ]))->get('/cookie-consent-test-page');

        $content = (string) $response->getContent();
        $this->assertStringContainsString("gtag('consent', 'update'", $content);
        $this->assertMatchesRegularExpression('/"analytics_storage": "granted"/', $content);
        $this->assertMatchesRegularExpression('/"ad_storage": "denied"/', $content);
        // The update follows the defaults
        $this->assertGreaterThan(
            strpos($content, "gtag('consent', 'default'"),
            strpos($content, "gtag('consent', 'update'"),
        );
    }

    public function test_scripts_of_rejected_categories_are_not_injected(): void
    {
        $response = $this->withUnencryptedCookie('guildforge_cookie_consent', $this->consentCookie([
            'necessary' => true,
            'analytics' => false,
            'marketing' => false,
        ]))->get('/cookie-consent-test-page');

        $this->assertStringNotContainsString(self::ANALYTICS_SCRIPT, (string) $response->getContent());
    }

    public function test_nothing_but_the_consent_mode_defaults_is_injected_without_consent(): void
    {
        $content = (string) $this->get('/cookie-consent-test-page')->getContent();

        $this->assertStringNotContainsString(self::ANALYTICS_SCRIPT, $content);
        $this->assertStringContainsString("gtag('consent', 'default'", $content);
        $this->assertStringNotContainsString("gtag('consent', 'update'", $content);
    }

    public function test_an_expired_consent_is_ignored(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.consent_validity_days', '30');

        $content = $this->pageWith($this->consentCookie(self::ACCEPTS_ANALYTICS, timestamp: now()->subDays(31)->toIso8601String()));

        $this->assertNoConsentApplied($content);
    }

    public function test_a_consent_still_within_its_validity_is_applied(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.consent_validity_days', '30');

        $content = $this->pageWith($this->consentCookie(self::ACCEPTS_ANALYTICS, timestamp: now()->subDays(29)->toIso8601String()));

        $this->assertStringContainsString(self::ANALYTICS_SCRIPT, $content);
        $this->assertStringContainsString("gtag('consent', 'update'", $content);
    }

    public function test_a_consent_given_for_an_older_configuration_is_ignored(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.config_version', '2');

        $content = $this->pageWith($this->consentCookie(self::ACCEPTS_ANALYTICS, version: 1));

        $this->assertNoConsentApplied($content);
    }

    public function test_an_older_configuration_is_accepted_when_consent_is_not_asked_again_on_changes(): void
    {
        $settings = app(SettingsServiceInterface::class);
        $settings->set('cookie-consent.config_version', '2');
        $settings->set('cookie-consent.reconsent_on_change', '0');

        $content = $this->pageWith($this->consentCookie(self::ACCEPTS_ANALYTICS, version: 1));

        $this->assertStringContainsString(self::ANALYTICS_SCRIPT, $content);
    }

    public function test_a_consent_without_a_valid_timestamp_is_ignored(): void
    {
        $content = $this->pageWith($this->consentCookie(self::ACCEPTS_ANALYTICS, timestamp: 'not a date'));

        $this->assertNoConsentApplied($content);
    }

    private function pageWith(string $cookie): string
    {
        return (string) $this->withUnencryptedCookie('guildforge_cookie_consent', $cookie)
            ->get('/cookie-consent-test-page')
            ->assertOk()
            ->getContent();
    }

    private function assertNoConsentApplied(string $content): void
    {
        $this->assertStringNotContainsString(self::ANALYTICS_SCRIPT, $content);
        $this->assertStringNotContainsString("gtag('consent', 'update'", $content);
        $this->assertStringContainsString("gtag('consent', 'default'", $content);
    }

    /**
     * The value the banner stores (consent-manager.ts).
     *
     * @param  array<string, bool>  $preferences
     */
    private function consentCookie(array $preferences, int $version = 1, ?string $timestamp = null): string
    {
        return (string) json_encode([
            'visitorId' => '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
            'version' => $version,
            'timestamp' => $timestamp ?? now()->toIso8601String(),
            'preferences' => $preferences,
        ]);
    }
}
