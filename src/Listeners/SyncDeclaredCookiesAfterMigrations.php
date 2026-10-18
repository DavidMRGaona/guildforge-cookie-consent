<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;
use Throwable;

final class SyncDeclaredCookiesAfterMigrations
{
    public function __construct(
        private readonly DeclaredCookieSynchronizerInterface $synchronizer,
    ) {}

    /**
     * Handles MigrationsEnded and NoPendingMigrations. A failing sync must never break migrate.
     */
    public function handle(object $event): void
    {
        try {
            $this->synchronizer->sync();
        } catch (Throwable $e) {
            Log::error('Syncing declared cookies after migrations failed', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
