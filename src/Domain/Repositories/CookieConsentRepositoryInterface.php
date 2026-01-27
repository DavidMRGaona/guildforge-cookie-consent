<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Repositories;

use App\Domain\ValueObjects\UserId;
use Modules\CookieConsent\Domain\Entities\CookieConsent;
use Modules\CookieConsent\Domain\ValueObjects\ConsentId;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;

interface CookieConsentRepositoryInterface
{
    public function findById(ConsentId $id): ?CookieConsent;

    /**
     * Find the most recent consent for a visitor.
     */
    public function findLatestByVisitor(VisitorId $visitorId): ?CookieConsent;

    /**
     * Find the most recent consent for a user.
     */
    public function findLatestByUser(UserId $userId): ?CookieConsent;

    /**
     * Find all consents for a visitor (audit history).
     *
     * @return array<CookieConsent>
     */
    public function findAllByVisitor(VisitorId $visitorId): array;

    public function save(CookieConsent $consent): void;

    /**
     * Count total consents.
     */
    public function count(): int;

    /**
     * Count consents by method.
     *
     * @return array<string, int>
     */
    public function countByMethod(): array;
}
