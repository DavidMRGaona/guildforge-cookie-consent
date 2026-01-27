<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Entities;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\Enums\CookieType;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\CookieId;

final class Cookie
{
    public function __construct(
        private readonly CookieId $id,
        private readonly CategoryId $categoryId,
        private string $name,
        private string $provider,
        private string $purpose,
        private CookieType $type,
        private ?string $domain = null,
        private ?string $duration = null,
        private bool $isActive = true,
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): CookieId
    {
        return $this->id;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function purpose(): string
    {
        return $this->purpose;
    }

    public function type(): CookieType
    {
        return $this->type;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    public function duration(): ?string
    {
        return $this->duration;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function update(
        string $name,
        string $provider,
        string $purpose,
        CookieType $type,
        ?string $domain = null,
        ?string $duration = null,
    ): void {
        $this->name = $name;
        $this->provider = $provider;
        $this->purpose = $purpose;
        $this->type = $type;
        $this->domain = $domain;
        $this->duration = $duration;
    }
}
