<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Repositories;

use Modules\CookieConsent\Domain\Entities\CookieScript;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\ScriptId;

interface CookieScriptRepositoryInterface
{
    public function findById(ScriptId $id): ?CookieScript;

    /**
     * @return array<CookieScript>
     */
    public function findByCategory(CategoryId $categoryId): array;

    /**
     * @param  array<string>  $categorySlugs
     * @return array<CookieScript>
     */
    public function findActiveByCategorySlugs(array $categorySlugs): array;

    public function save(CookieScript $script): void;

    public function delete(ScriptId $id): void;

    public function exists(ScriptId $id): bool;
}
