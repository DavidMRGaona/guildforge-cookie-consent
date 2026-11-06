<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature;

use Modules\CookieConsent\Filament\Pages\CookieConsentSettings;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The module's help guides pass the knowledge base lint, so CI blocks a broken one.
 */
final class KnowledgeGuidesTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    public function test_guides_are_valid(): void
    {
        // The panel guides link to the module's panel pages, routed only once they are on the panel
        $this->registerOnAdminPanel(
            [CookieCategoryResource::class, CookieConsentResource::class],
            [CookieConsentSettings::class],
        );

        $this->assertModuleKnowledgeGuidesAreValid('cookie-consent');
    }
}
