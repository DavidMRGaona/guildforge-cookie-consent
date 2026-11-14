<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Unit\Frontend;

use Modules\CookieConsent\CookieConsentServiceProvider;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The core tells the page whether a consent module is active (shared prop `cookieConsentModule`)
 * from this declaration, so whoever waits for the banner (pop-ups) can tell «no banner» from
 * «banner not loaded yet».
 */
final class ConsentProviderDeclarationTest extends ModuleTestCase
{
    public function test_the_module_declares_itself_the_cookie_consent_provider(): void
    {
        $provider = new CookieConsentServiceProvider($this->app);

        $this->assertTrue($provider->providesCookieConsent());
    }
}
