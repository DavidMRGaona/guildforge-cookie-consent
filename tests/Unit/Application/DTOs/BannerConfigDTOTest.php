<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Unit\Application\DTOs;

use Modules\CookieConsent\Application\DTOs\BannerConfigDTO;
use PHPUnit\Framework\TestCase;

final class BannerConfigDTOTest extends TestCase
{
    public function test_to_array_has_no_logo_or_blocking_keys(): void
    {
        $config = new BannerConfigDTO(
            position: 'bottom',
            layout: 'bar',
            theme: 'light',
            colors: [
                'primary' => '#10B981',
                'secondary' => '#6B7280',
                'background' => '#FFFFFF',
                'text' => '#1F2937',
            ],
            showRejectAll: true,
            showSettingsButton: true,
            validityDays: 365,
            reconsentOnChange: true,
            configVersion: 1,
            texts: [
                'title' => 'Utilizamos cookies',
                'description' => 'Usamos cookies propias y de terceros.',
                'acceptAll' => 'Aceptar todas',
                'rejectAll' => 'Rechazar todas',
                'settings' => 'Configurar',
                'save' => 'Guardar preferencias',
                'privacyLink' => 'Política de privacidad',
                'privacyUrl' => '/politica-de-privacidad',
            ],
            gtmEnabled: false,
            gtmContainerId: '',
            consentModeEnabled: true,
        );

        $array = $config->toArray();

        $this->assertArrayNotHasKey('showLogo', $array);
        $this->assertArrayNotHasKey('blockPageUntilConsent', $array);
        $this->assertSame([
            'position',
            'layout',
            'theme',
            'colors',
            'showRejectAll',
            'showSettingsButton',
            'validityDays',
            'reconsentOnChange',
            'configVersion',
            'texts',
            'gtmEnabled',
            'gtmContainerId',
            'consentModeEnabled',
        ], array_keys($array));
    }
}
