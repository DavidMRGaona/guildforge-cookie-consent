<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Console\Commands;

use Illuminate\Console\Command;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;

final class SyncDeclaredCookiesCommand extends Command
{
    protected $signature = 'cookie-consent:sync-declared-cookies';

    protected $description = 'Create the cookies declared by modules that are missing from the cookie list';

    public function handle(DeclaredCookieSynchronizerInterface $synchronizer): int
    {
        $created = $synchronizer->sync();

        $this->info("Declared cookies created: {$created}");

        return self::SUCCESS;
    }
}
