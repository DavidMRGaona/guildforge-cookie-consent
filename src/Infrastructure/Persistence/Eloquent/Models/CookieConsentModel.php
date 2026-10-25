<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;

/**
 * @property string $id
 * @property string $visitor_id
 * @property string|null $user_id
 * @property string $ip_hash
 * @property string $user_agent
 * @property array<string, bool> $preferences
 * @property int $config_version
 * @property ConsentMethod $consent_method
 * @property Carbon $consented_at
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read UserModel|null $user
 */
final class CookieConsentModel extends Model
{
    use HasUuids;

    protected $table = 'cookie_consent_consents';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'visitor_id',
        'user_id',
        'ip_hash',
        'user_agent',
        'preferences',
        'config_version',
        'consent_method',
        'consented_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferences' => 'array',
            'config_version' => 'integer',
            'consent_method' => ConsentMethod::class,
            'consented_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
