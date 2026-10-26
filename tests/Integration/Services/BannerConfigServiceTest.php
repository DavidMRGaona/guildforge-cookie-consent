<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Services;

use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The settings modal takes every label from the banner texts, so none of them is
 * written by hand in the template.
 */
final class BannerConfigServiceTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    public function test_texts_carry_the_settings_modal_labels(): void
    {
        $texts = $this->app->make(BannerConfigServiceInterface::class)->getBannerConfig()->texts;

        $this->assertSame([
            'required' => 'Obligatoria',
            'showCookies' => 'Ver cookies',
            'cookieColumn' => 'Cookie',
            'providerColumn' => 'Proveedor',
            'durationColumn' => 'Duración',
            'cancel' => 'Cancelar',
            'close' => 'Cerrar',
        ], array_intersect_key($texts, array_flip([
            'required',
            'showCookies',
            'cookieColumn',
            'providerColumn',
            'durationColumn',
            'cancel',
            'close',
        ])));
    }
}
