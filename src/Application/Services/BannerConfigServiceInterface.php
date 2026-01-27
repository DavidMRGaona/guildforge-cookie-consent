<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

use Modules\CookieConsent\Application\DTOs\BannerConfigDTO;

interface BannerConfigServiceInterface
{
    /**
     * Get the banner configuration.
     */
    public function getBannerConfig(): BannerConfigDTO;

    /**
     * Get the number of days consent is valid.
     */
    public function getConsentValidityDays(): int;
}
