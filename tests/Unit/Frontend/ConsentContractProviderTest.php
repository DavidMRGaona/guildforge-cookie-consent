<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;

/**
 * Source guards, not behaviour tests: the module has no Vue test runner. The banner answers the
 * core's cookie consent contract for every other module, which the module build must share with
 * the page instead of bundling its own copy.
 */
final class ConsentContractProviderTest extends TestCase
{
    private const string ROOT = __DIR__.'/../../../';

    public function test_the_banner_registers_as_the_consent_provider(): void
    {
        $source = $this->source('resources/js/components/CookieBanner.vue');

        $this->assertStringContainsString("from '@/contracts/cookieConsent'", $source);
        $this->assertStringContainsString('registerCookieConsentProvider({', $source);
        $this->assertStringNotContainsString('GuildForgeCookieConsent', $source);
        $this->assertStringNotContainsString('guildforge:cookie-consent-changed', $source);
    }

    public function test_every_saved_choice_notifies_the_contract(): void
    {
        $source = $this->source('resources/js/components/CookieBanner.vue');

        foreach (['acceptAll', 'rejectAll', 'handleSaveSettings'] as $function) {
            $start = strpos($source, "async function {$function}(");
            $this->assertNotFalse($start, "{$function} exists");

            $end = strpos($source, "\n}\n", $start);
            $this->assertNotFalse($end);

            $this->assertStringContainsString('notifyCookieConsentChanged();', substr($source, $start, $end - $start), $function);
        }
    }

    public function test_the_build_shares_the_core_public_api_instead_of_bundling_it(): void
    {
        // The core's build keeps its public API, the consent contract included, out of the bundle
        $this->assertStringContainsString("import { defineModuleConfig } from '../../vite.module.config.ts';", $this->source('vite.config.ts'));
        $this->assertStringContainsString("defineModuleConfig({ name: 'cookie-consent', root: import.meta.dirname })", $this->source('vite.config.ts'));
    }

    private function source(string $file): string
    {
        $contents = file_get_contents(self::ROOT.$file);
        $this->assertNotFalse($contents, "{$file} is readable");

        return $contents;
    }
}
