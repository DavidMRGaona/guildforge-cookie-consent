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

    public function test_the_theme_follows_the_site_mode_when_none_was_chosen(): void
    {
        $this->assertSame('auto', $this->app->make(BannerConfigServiceInterface::class)->getBannerConfig()->theme);
    }

    public function test_texts_carry_the_message_for_a_choice_that_was_not_saved(): void
    {
        $texts = $this->app->make(BannerConfigServiceInterface::class)->getBannerConfig()->texts;

        $this->assertSame(
            'No hemos podido guardar tu elección. Vuelve a intentarlo y, si sigue fallando, recarga la página.',
            $texts['saveError'],
        );
    }
}
