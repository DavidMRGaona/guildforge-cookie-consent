<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Repositories;

use Modules\CookieConsent\Domain\Entities\CookieCategory;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;

interface CookieCategoryRepositoryInterface
{
    public function findById(CategoryId $id): ?CookieCategory;

    public function findBySlug(string $slug): ?CookieCategory;

    /**
     * @return array<CookieCategory>
     */
    public function findAll(): array;

    /**
     * @return array<CookieCategory>
     */
    public function findRequired(): array;

    public function save(CookieCategory $category): void;

    public function delete(CategoryId $id): void;

    public function exists(CategoryId $id): bool;

    public function slugExists(string $slug, ?CategoryId $excludeId = null): bool;
}
