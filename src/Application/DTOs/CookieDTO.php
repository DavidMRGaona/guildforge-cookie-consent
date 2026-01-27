<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Entities\Cookie;

final readonly class CookieDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $provider,
        public ?string $domain,
        public string $purpose,
        public string $type,
        public ?string $duration,
    ) {}

    public static function fromEntity(Cookie $cookie): self
    {
        return new self(
            id: $cookie->id()->value(),
            name: $cookie->name(),
            provider: $cookie->provider(),
            domain: $cookie->domain(),
            purpose: $cookie->purpose(),
            type: $cookie->type()->value,
            duration: $cookie->duration(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'domain' => $this->domain,
            'purpose' => $this->purpose,
            'type' => $this->type,
            'duration' => $this->duration,
        ];
    }
}
