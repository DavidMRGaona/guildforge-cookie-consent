<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $script_head
 * @property string|null $script_body_start
 * @property string|null $script_body_end
 * @property string|null $noscript_content
 * @property int $sort_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read CookieCategoryModel $category
 */
final class CookieScriptModel extends Model
{
    use HasUuids;

    protected $table = 'cookie_consent_scripts';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'category_id',
        'name',
        'description',
        'script_head',
        'script_body_start',
        'script_body_end',
        'noscript_content',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
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
