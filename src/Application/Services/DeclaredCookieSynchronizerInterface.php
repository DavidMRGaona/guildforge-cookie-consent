<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

interface DeclaredCookieSynchronizerInterface
{
    /**
     * Creates declared cookies missing by name; returns how many were created.
     */
    public function sync(): int;
}
