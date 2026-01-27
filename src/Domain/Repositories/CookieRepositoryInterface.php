<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Repositories;

use Modules\CookieConsent\Domain\Entities\Cookie;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\CookieId;

interface CookieRepositoryInterface
{
    public function findById(CookieId $id): ?Cookie;

    /**
     * @return array<Cookie>
     */
    public function findByCategory(CategoryId $categoryId): array;

    /**
     * @param  array<CategoryId>  $categoryIds
     * @return array<Cookie>
     */
    public function findActiveByCategoryIds(array $categoryIds): array;

    public function save(Cookie $cookie): void;

    public function delete(CookieId $id): void;

    public function exists(CookieId $id): bool;
}
