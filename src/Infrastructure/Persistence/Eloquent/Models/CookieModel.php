<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CookieConsent\Domain\Enums\CookieType;

/**
 * @property string $id
 * @property string $category_id
 * @property string $name
 * @property string $provider
 * @property string|null $domain
 * @property string $purpose
 * @property CookieType $type
 * @property string|null $duration
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CookieCategoryModel $category
 */
final class CookieModel extends Model
{
    use HasUuids;

    protected $table = 'cookie_consent_cookies';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'category_id',
        'name',
        'provider',
        'domain',
        'purpose',
        'type',
        'duration',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CookieType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CookieCategoryModel, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CookieCategoryModel::class, 'category_id');
    }
}
