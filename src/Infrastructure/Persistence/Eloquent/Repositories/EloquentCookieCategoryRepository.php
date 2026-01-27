<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\Entities\CookieCategory;
use Modules\CookieConsent\Domain\Enums\ConsentModeKey;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

final class EloquentCookieCategoryRepository implements CookieCategoryRepositoryInterface
{
    public function findById(CategoryId $id): ?CookieCategory
    {
        $model = CookieCategoryModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findBySlug(string $slug): ?CookieCategory
    {
        $model = CookieCategoryModel::where('slug', $slug)->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return array<CookieCategory>
     */
    public function findAll(): array
    {
        return CookieCategoryModel::orderBy('sort_order')
            ->get()
            ->map(fn (CookieCategoryModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * @return array<CookieCategory>
     */
    public function findRequired(): array
    {
        return CookieCategoryModel::where('is_required', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CookieCategoryModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(CookieCategory $category): void
    {
        CookieCategoryModel::updateOrCreate(
            ['id' => $category->id()->value()],
            [
                'name' => $category->name(),
                'slug' => $category->slug(),
                'description' => $category->description(),
                'is_required' => $category->isRequired(),
                'sort_order' => $category->sortOrder(),
                'consent_mode_keys' => $category->consentModeKeysAsStrings(),
            ]
        );
    }

    public function delete(CategoryId $id): void
    {
        CookieCategoryModel::destroy($id->value());
    }

    public function exists(CategoryId $id): bool
    {
        return CookieCategoryModel::where('id', $id->value())->exists();
    }

    public function slugExists(string $slug, ?CategoryId $excludeId = null): bool
    {
        $query = CookieCategoryModel::where('slug', $slug);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId->value());
        }

        return $query->exists();
    }

    private function toEntity(CookieCategoryModel $model): CookieCategory
    {
        $consentModeKeys = [];
        if (is_array($model->consent_mode_keys)) {
            foreach ($model->consent_mode_keys as $key) {
                $enum = ConsentModeKey::tryFrom($key);
                if ($enum !== null) {
                    $consentModeKeys[] = $enum;
                }
            }
        }

        return new CookieCategory(
            id: CategoryId::fromString($model->id),
            name: $model->name,
            slug: $model->slug,
            description: $model->description,
            isRequired: $model->is_required,
            sortOrder: $model->sort_order,
            consentModeKeys: $consentModeKeys,
            createdAt: $model->created_at !== null
                ? DateTimeImmutable::createFromMutable($model->created_at->toDateTime())
                : null,
            updatedAt: $model->updated_at !== null
                ? DateTimeImmutable::createFromMutable($model->updated_at->toDateTime())
                : null,
        );
    }
}
