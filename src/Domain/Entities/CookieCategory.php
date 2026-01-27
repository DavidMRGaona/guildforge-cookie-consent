<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Entities;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\Enums\ConsentModeKey;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;

final class CookieCategory
{
    /**
     * @param  array<ConsentModeKey>  $consentModeKeys
     */
    public function __construct(
        private readonly CategoryId $id,
        private string $name,
        private string $slug,
        private string $description,
        private bool $isRequired,
        private int $sortOrder,
        private array $consentModeKeys = [],
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function sortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @return array<ConsentModeKey>
     */
    public function consentModeKeys(): array
    {
        return $this->consentModeKeys;
    }

    /**
     * Get consent mode keys as string array for JavaScript.
     *
     * @return array<string>
     */
    public function consentModeKeysAsStrings(): array
    {
        return array_map(fn (ConsentModeKey $key) => $key->value, $this->consentModeKeys);
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param  array<ConsentModeKey>  $consentModeKeys
     */
    public function update(
        string $name,
        string $slug,
        string $description,
        int $sortOrder,
        array $consentModeKeys = [],
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->sortOrder = $sortOrder;
        $this->consentModeKeys = $consentModeKeys;
    }
}
