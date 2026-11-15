<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Tests\Support\Modules\ModuleTestCase;

/**
 * Whoever waits for the banner (pop-ups) tells «no banner» from «banner not loaded yet» with the
 * shared prop `cookieConsentModule`. It must reach the browser in a real Inertia response, on the
 * first load and on every visit, not only in the middleware's unit test.
 */
final class ConsentModuleInertiaPropTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/cookie-consent-inertia-test', fn () => Inertia::render('Home'));
    }

    public function test_the_first_page_load_carries_the_consent_module_prop(): void
    {
        $page = $this->firstLoadPage();

        $this->assertTrue($page['props']['cookieConsentModule']);
    }

    public function test_an_inertia_visit_carries_the_consent_module_prop_while_the_module_is_active(): void
    {
        $response = $this->get('/cookie-consent-inertia-test', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $this->firstLoadPage()['version'],
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('props.cookieConsentModule'));
    }

    /**
     * @return array{version: string, props: array<string, mixed>}
     */
    private function firstLoadPage(): array
    {
        $response = $this->get('/cookie-consent-inertia-test');
        $response->assertOk();

        $this->assertSame(
            1,
            preg_match('/<script data-page="app" type="application\/json">(.*?)<\/script>/s', (string) $response->getContent(), $matches),
        );

        /** @var array{version: string, props: array<string, mixed>} */
        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
