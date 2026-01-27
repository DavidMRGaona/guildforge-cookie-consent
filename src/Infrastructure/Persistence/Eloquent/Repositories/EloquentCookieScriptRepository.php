<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\CookieConsent\Domain\Entities\CookieScript;
use Modules\CookieConsent\Domain\Repositories\CookieScriptRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;
use Modules\CookieConsent\Domain\ValueObjects\ScriptId;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieScriptModel;

final class EloquentCookieScriptRepository implements CookieScriptRepositoryInterface
{
    public function findById(ScriptId $id): ?CookieScript
    {
        $model = CookieScriptModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return array<CookieScript>
     */
    public function findByCategory(CategoryId $categoryId): array
    {
        return CookieScriptModel::where('category_id', $categoryId->value())
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CookieScriptModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * @param  array<string>  $categorySlugs
     * @return array<CookieScript>
     */
    public function findActiveByCategorySlugs(array $categorySlugs): array
    {
        $categoryIds = CookieCategoryModel::whereIn('slug', $categorySlugs)
            ->pluck('id')
            ->all();

        return CookieScriptModel::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CookieScriptModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(CookieScript $script): void
    {
        CookieScriptModel::updateOrCreate(
            ['id' => $script->id()->value()],
            [
                'category_id' => $script->categoryId()->value(),
                'name' => $script->name(),
                'description' => $script->description(),
                'script_head' => $script->scriptHead(),
                'script_body_start' => $script->scriptBodyStart(),
                'script_body_end' => $script->scriptBodyEnd(),
                'noscript_content' => $script->noscriptContent(),
                'sort_order' => $script->sortOrder(),
                'is_active' => $script->isActive(),
            ]
        );
    }

    public function delete(ScriptId $id): void
    {
        CookieScriptModel::destroy($id->value());
    }

    public function exists(ScriptId $id): bool
    {
        return CookieScriptModel::where('id', $id->value())->exists();
    }

    private function toEntity(CookieScriptModel $model): CookieScript
    {
        return new CookieScript(
            id: ScriptId::fromString($model->id),
            categoryId: CategoryId::fromString($model->category_id),
            name: $model->name,
            description: $model->description,
            scriptHead: $model->script_head,
            scriptBodyStart: $model->script_body_start,
            scriptBodyEnd: $model->script_body_end,
            noscriptContent: $model->noscript_content,
            sortOrder: $model->sort_order,
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
