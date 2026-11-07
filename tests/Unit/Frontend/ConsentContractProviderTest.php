<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;

/**
 * The banner answers the core's cookie consent contract for every other module (see
 * tests/js/consentContract.spec.ts), and the module build must share that contract with
 * the page instead of bundling its own copy.
 */
final class ConsentContractProviderTest extends TestCase
{
    private const string ROOT = __DIR__.'/../../../';

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
