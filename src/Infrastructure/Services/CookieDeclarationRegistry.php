<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Modules\CookieConsent\Application\DTOs\DeclaredCookieDTO;
use Modules\CookieConsent\Application\Services\CookieDeclarationRegistryInterface;

final class CookieDeclarationRegistry implements CookieDeclarationRegistryInterface
{
    /** @var array<string, DeclaredCookieDTO> */
    private array $cookies = [];

    public function declare(DeclaredCookieDTO $cookie): void
    {
        $this->cookies[$cookie->name] = $cookie;
    }

    /**
     * @return list<DeclaredCookieDTO>
     */
    public function all(): array
    {
        return array_values($this->cookies);
    }
}
