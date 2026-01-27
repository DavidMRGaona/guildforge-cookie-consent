<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use Modules\CookieConsent\Domain\Entities\CookieConsent;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Domain\Repositories\CookieConsentRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\ConsentId;
use Modules\CookieConsent\Domain\ValueObjects\ConsentPreferences;
use Modules\CookieConsent\Domain\ValueObjects\IpHash;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;

final class EloquentCookieConsentRepository implements CookieConsentRepositoryInterface
{
    public function findById(ConsentId $id): ?CookieConsent
    {
        $model = CookieConsentModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findLatestByVisitor(VisitorId $visitorId): ?CookieConsent
    {
        $model = CookieConsentModel::where('visitor_id', $visitorId->value())
            ->orderBy('consented_at', 'desc')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findLatestByUser(UserId $userId): ?CookieConsent
    {
        $model = CookieConsentModel::where('user_id', $userId->value())
            ->orderBy('consented_at', 'desc')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * @return array<CookieConsent>
     */
    public function findAllByVisitor(VisitorId $visitorId): array
    {
        return CookieConsentModel::where('visitor_id', $visitorId->value())
            ->orderBy('consented_at', 'desc')
            ->get()
            ->map(fn (CookieConsentModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(CookieConsent $consent): void
    {
        CookieConsentModel::updateOrCreate(
            ['id' => $consent->id()->value()],
            [
                'visitor_id' => $consent->visitorId()->value(),
                'user_id' => $consent->userId()?->value(),
                'ip_hash' => $consent->ipHash()->value(),
                'user_agent' => $consent->userAgent(),
                'preferences' => $consent->preferences()->toArray(),
                'config_version' => $consent->configVersion(),
                'consent_method' => $consent->consentMethod()->value,
                'consented_at' => $consent->consentedAt(),
                'expires_at' => $consent->expiresAt(),
            ]
        );
    }

    public function count(): int
    {
        return CookieConsentModel::count();
    }

    /**
     * @return array<string, int>
     */
    public function countByMethod(): array
    {
        return CookieConsentModel::query()
            ->selectRaw('consent_method, count(*) as count')
            ->groupBy('consent_method')
            ->pluck('count', 'consent_method')
            ->all();
    }

    private function toEntity(CookieConsentModel $model): CookieConsent
    {
        /** @var ConsentMethod $consentMethod */
        $consentMethod = $model->consent_method;

        return new CookieConsent(
            id: ConsentId::fromString($model->id),
            visitorId: VisitorId::fromString($model->visitor_id),
            ipHash: IpHash::fromHash($model->ip_hash),
            userAgent: $model->user_agent,
            preferences: ConsentPreferences::fromArray($model->preferences),
            configVersion: $model->config_version,
            consentMethod: $consentMethod,
            consentedAt: DateTimeImmutable::createFromMutable($model->consented_at->toDateTime()),
            expiresAt: DateTimeImmutable::createFromMutable($model->expires_at->toDateTime()),
            userId: $model->user_id !== null ? UserId::fromString($model->user_id) : null,
            createdAt: $model->created_at !== null
                ? DateTimeImmutable::createFromMutable($model->created_at->toDateTime())
                : null,
            updatedAt: $model->updated_at !== null
                ? DateTimeImmutable::createFromMutable($model->updated_at->toDateTime())
                : null,
        );
    }
}
