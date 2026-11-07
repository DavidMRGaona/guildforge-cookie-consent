<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Http;

use App\Application\Services\SettingsServiceInterface;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Google Tag Manager waits for the visitor: with Consent Mode it loads on every page and
 * follows the consent signals; without it, only once the visitor accepts its category.
 */
final class GoogleTagManagerInjectionTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    private const string CONTAINER = 'GTM-TEST123';

    private const string GTM_SCRIPT = 'googletagmanager.com/gtm.js';

    private const string GTM_NOSCRIPT = 'googletagmanager.com/ns.html?id=GTM-TEST123';

    private const array ACCEPTS_ANALYTICS = ['necessary' => true, 'analytics' => true, 'marketing' => false];

    private const array REJECTS_ALL = ['necessary' => true, 'analytics' => false, 'marketing' => false];

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/cookie-consent-gtm-page', fn () => response(
            '<html><head><title>Test</title></head><body><p>Contenido</p></body></html>',
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        ));

        $settings = app(SettingsServiceInterface::class);
        $settings->set('cookie-consent.gtm_enabled', '1');
        $settings->set('cookie-consent.gtm_container_id', self::CONTAINER);
    }

    public function test_without_consent_mode_the_container_waits_for_a_choice(): void
    {
        $this->withoutConsentMode();

        $content = $this->page(null);

        $this->assertStringNotContainsString(self::GTM_SCRIPT, $content);
        $this->assertStringNotContainsString(self::GTM_NOSCRIPT, $content);
    }

    public function test_without_consent_mode_the_container_is_not_loaded_when_its_category_is_rejected(): void
    {
        $this->withoutConsentMode();

        $content = $this->page(self::REJECTS_ALL);

        $this->assertStringNotContainsString(self::GTM_SCRIPT, $content);
        $this->assertStringNotContainsString(self::GTM_NOSCRIPT, $content);
    }

    public function test_without_consent_mode_the_container_loads_once_its_category_is_accepted(): void
    {
        $this->withoutConsentMode();

        $content = $this->page(self::ACCEPTS_ANALYTICS);

        $this->assertStringContainsString(self::GTM_SCRIPT, $content);
        $this->assertStringContainsString(self::GTM_NOSCRIPT, $content);
    }

    public function test_the_category_of_the_container_is_a_setting(): void
    {
        $this->withoutConsentMode();
        app(SettingsServiceInterface::class)->set('cookie-consent.gtm_category', 'marketing');

        $this->assertStringNotContainsString(self::GTM_SCRIPT, $this->page(self::ACCEPTS_ANALYTICS));
        $this->assertStringContainsString(self::GTM_SCRIPT, $this->page(['necessary' => true, 'analytics' => false, 'marketing' => true]));
    }

    public function test_the_container_waits_for_analytics_when_no_category_was_chosen(): void
    {
        $this->withoutConsentMode();

        $config = $this->get('/consentimiento-cookies/configuracion')->assertOk()->json('data');

        $this->assertSame('analytics', $config['gtmCategory']);
    }

    public function test_with_consent_mode_the_container_loads_before_any_choice(): void
    {
        $content = $this->page(null);

        $this->assertStringContainsString(self::GTM_SCRIPT, $content);
        // Consent Mode defaults first, so the container starts with everything denied
        $this->assertGreaterThan(
            strpos($content, "gtag('consent', 'default'"),
            strpos($content, self::GTM_SCRIPT),
        );
    }

    public function test_with_consent_mode_the_noscript_container_still_waits_for_its_category(): void
    {
        // Without JavaScript there are no consent signals to follow
        $this->assertStringNotContainsString(self::GTM_NOSCRIPT, $this->page(null));
        $this->assertStringNotContainsString(self::GTM_NOSCRIPT, $this->page(self::REJECTS_ALL));
        $this->assertStringContainsString(self::GTM_NOSCRIPT, $this->page(self::ACCEPTS_ANALYTICS));
    }

    #[DataProvider('notAContainerId')]
    public function test_a_malformed_container_id_is_never_injected(string $containerId): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.gtm_container_id', $containerId);

        $content = $this->page(self::ACCEPTS_ANALYTICS);

        $this->assertStringNotContainsString(self::GTM_SCRIPT, $content);
        $this->assertStringNotContainsString('googletagmanager.com/ns.html', $content);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAContainerId(): array
    {
        return [
            'script injection' => ["GTM-ABC');alert(1);//"],
            'markup injection' => ['GTM-ABC"><script>alert(1)</script>'],
            'another tag id' => ['G-ABC123'],
            'blank' => ['   '],
        ];
    }

    private function withoutConsentMode(): void
    {
        app(SettingsServiceInterface::class)->set('cookie-consent.consent_mode_enabled', '0');
    }

    /**
     * @param  array<string, bool>|null  $preferences  null: the visitor has not chosen yet
     */
    private function page(?array $preferences): string
    {
        $request = $preferences === null
            ? $this
            : $this->withUnencryptedCookie('guildforge_cookie_consent', (string) json_encode([
                'visitorId' => '0b5d3c1e-7a1f-4c2b-9d3e-5f6a7b8c9d0e',
                'version' => 1,
                'timestamp' => now()->toIso8601String(),
                'preferences' => $preferences,
            ]));

        return (string) $request->get('/cookie-consent-gtm-page')->assertOk()->getContent();
    }
}
