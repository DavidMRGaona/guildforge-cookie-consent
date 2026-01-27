<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property bool $is_required
 * @property bool $is_system
 * @property int $sort_order
 * @property array<string>|null $consent_mode_keys
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CookieModel> $cookies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CookieScriptModel> $scripts
 */
final class CookieCategoryModel extends Model
{
    use HasUuids;

    protected $table = 'cookie_consent_categories';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'is_required',
        'is_system',
        'sort_order',
        'consent_mode_keys',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
            'consent_mode_keys' => 'array',
        ];
    }

    /**
     * @return HasMany<CookieModel, $this>
     */
    public function cookies(): HasMany
    {
        return $this->hasMany(CookieModel::class, 'category_id');
    }

    /**
     * @return HasMany<CookieScriptModel, $this>
     */
    public function scripts(): HasMany
    {
        return $this->hasMany(CookieScriptModel::class, 'category_id');
    }
}
