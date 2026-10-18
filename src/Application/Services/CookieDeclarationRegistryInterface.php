<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

use Modules\CookieConsent\Application\DTOs\DeclaredCookieDTO;

interface CookieDeclarationRegistryInterface
{
    /**
     * Declares a cookie; declaring the same name again replaces the earlier entry.
     */
    public function declare(DeclaredCookieDTO $cookie): void;

    /**
     * @return list<DeclaredCookieDTO>
     */
    public function all(): array;
}
