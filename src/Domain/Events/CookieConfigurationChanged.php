<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when cookie categories, cookies, or scripts are modified.
 * Triggers config version increment for re-consent.
 */
final readonly class CookieConfigurationChanged
{
    use Dispatchable;

    public function __construct(
        public string $changeType,
        public string $entityType,
        public string $entityId,
    ) {}
}
