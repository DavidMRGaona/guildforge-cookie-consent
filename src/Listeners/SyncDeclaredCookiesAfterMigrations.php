<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Listeners;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;
use Throwable;

final class SyncDeclaredCookiesAfterMigrations
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Handles MigrationsEnded and NoPendingMigrations. A failing sync must never break migrate.
     */
    public function handle(MigrationsEnded|NoPendingMigrations $event): void
    {
        // Rollbacks and --pretend runs must not write anything
        if ($event->method !== 'up') {
            return;
        }

        if ($event instanceof MigrationsEnded && ($event->options['pretend'] ?? false)) {
            return;
        }

        try {
            // Resolved here, not injected: building the synchronizer builds the registry, which
            // runs the declaration callbacks of other modules (they may throw)
            $synchronizer = $this->container->make(DeclaredCookieSynchronizerInterface::class);

            // A savepoint when migrate runs inside a transaction: on PostgreSQL a failed statement
            // would otherwise leave that whole transaction aborted
            DB::transaction(static fn (): int => $synchronizer->sync());
        } catch (Throwable $e) {
            Log::error('Syncing declared cookies after migrations failed', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
