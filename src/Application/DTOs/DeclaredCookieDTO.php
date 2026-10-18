<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Enums\CookieType;

final readonly class DeclaredCookieDTO
{
    public function __construct(
        public string $name,
        public string $categorySlug,
        public string $provider,
        public string $purpose,
        public ?string $duration = null,
        public CookieType $type = CookieType::FirstParty,
    ) {}
}
