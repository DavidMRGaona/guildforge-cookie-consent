<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;

final class IncrementConfigVersionOnChange
{
    public function __construct(
        private readonly ConsentServiceInterface $consentService,
    ) {}

    public function handle(CookieConfigurationChanged $event): void
    {
        $this->consentService->incrementConfigVersion();

        Log::info('Cookie consent config version incremented', [
            'change_type' => $event->changeType,
            'entity_type' => $event->entityType,
            'entity_id' => $event->entityId,
        ]);
    }
}
