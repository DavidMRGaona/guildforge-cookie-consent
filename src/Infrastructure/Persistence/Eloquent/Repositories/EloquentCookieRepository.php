<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\Entities\Cookie;
use Modules\CookieConsent\Domain\Enums\CookieType;
use Modules\CookieConsent\Domain\Repositories\CookieRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\CookieId;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieModel;

final class EloquentCookieRepository implements CookieRepositoryInterface
{
    public function findById(CookieId $id): ?Cookie
    {
        $model = CookieModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return array<Cookie>
     */
    public function findByCategory(CategoryId $categoryId): array
    {
        return CookieModel::where('category_id', $categoryId->value())
            ->orderBy('name')
            ->get()
            ->map(fn (CookieModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * @param  array<CategoryId>  $categoryIds
     * @return array<Cookie>
     */
    public function findActiveByCategoryIds(array $categoryIds): array
    {
        $ids = array_map(fn (CategoryId $id) => $id->value(), $categoryIds);

        return CookieModel::whereIn('category_id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (CookieModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Cookie $cookie): void
    {
        CookieModel::updateOrCreate(
            ['id' => $cookie->id()->value()],
            [
                'category_id' => $cookie->categoryId()->value(),
                'name' => $cookie->name(),
                'provider' => $cookie->provider(),
                'domain' => $cookie->domain(),
                'purpose' => $cookie->purpose(),
                'type' => $cookie->type()->value,
                'duration' => $cookie->duration(),
                'is_active' => $cookie->isActive(),
            ]
        );
    }

    public function delete(CookieId $id): void
    {
        CookieModel::destroy($id->value());
    }

    public function exists(CookieId $id): bool
    {
        return CookieModel::where('id', $id->value())->exists();
    }

    private function toEntity(CookieModel $model): Cookie
    {
        /** @var CookieType $type */
        $type = $model->type;

        return new Cookie(
            id: CookieId::fromString($model->id),
            categoryId: CategoryId::fromString($model->category_id),
            name: $model->name,
            provider: $model->provider,
            purpose: $model->purpose,
            type: $type,
            domain: $model->domain,
            duration: $model->duration,
            isActive: $model->is_active,
            createdAt: $model->created_at !== null
                ? DateTimeImmutable::createFromMutable($model->created_at->toDateTime())
                : null,
            updatedAt: $model->updated_at !== null
                ? DateTimeImmutable::createFromMutable($model->updated_at->toDateTime())
                : null,
        );
    }
}
