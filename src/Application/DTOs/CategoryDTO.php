<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Entities\CookieCategory;

final readonly class CategoryDTO
{
    /**
     * @param  array<string>  $consentModeKeys
     * @param  array<CookieDTO>  $cookies
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $description,
        public bool $isRequired,
        public int $sortOrder,
        public array $consentModeKeys = [],
        public array $cookies = [],
    ) {}

    /**
     * @param  array<CookieDTO>  $cookies
     */
    public static function fromEntity(CookieCategory $category, array $cookies = []): self
    {
        return new self(
            id: $category->id()->value(),
            name: $category->name(),
            slug: $category->slug(),
            description: $category->description(),
            isRequired: $category->isRequired(),
            sortOrder: $category->sortOrder(),
            consentModeKeys: $category->consentModeKeysAsStrings(),
            cookies: $cookies,
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
            'slug' => $this->slug,
            'description' => $this->description,
            'isRequired' => $this->isRequired,
            'sortOrder' => $this->sortOrder,
            'consentModeKeys' => $this->consentModeKeys,
            'cookies' => array_map(fn (CookieDTO $cookie) => $cookie->toArray(), $this->cookies),
        ];
    }
}
