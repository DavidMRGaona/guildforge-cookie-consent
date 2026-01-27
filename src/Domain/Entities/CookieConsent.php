<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Entities;

use App\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Domain\ValueObjects\ConsentId;
use Modules\CookieConsent\Domain\ValueObjects\ConsentPreferences;
use Modules\CookieConsent\Domain\ValueObjects\IpHash;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;

final class CookieConsent
{
    public function __construct(
        private readonly ConsentId $id,
        private readonly VisitorId $visitorId,
        private readonly IpHash $ipHash,
        private readonly string $userAgent,
        private readonly ConsentPreferences $preferences,
        private readonly int $configVersion,
        private readonly ConsentMethod $consentMethod,
        private readonly DateTimeImmutable $consentedAt,
        private readonly DateTimeImmutable $expiresAt,
        private readonly ?UserId $userId = null,
        private readonly ?DateTimeImmutable $createdAt = null,
        private readonly ?DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): ConsentId
    {
        return $this->id;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function ipHash(): IpHash
    {
        return $this->ipHash;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    public function preferences(): ConsentPreferences
    {
        return $this->preferences;
    }

    public function configVersion(): int
    {
        return $this->configVersion;
    }

    public function consentMethod(): ConsentMethod
    {
        return $this->consentMethod;
    }

    public function consentedAt(): DateTimeImmutable
    {
        return $this->consentedAt;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Check if consent is still valid (not expired).
     */
    public function isValid(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable;

        return $now < $this->expiresAt;
    }

    /**
     * Check if consent has expired.
     */
    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        return ! $this->isValid($now);
    }

    /**
     * Check if consent is still valid for the current config version.
     */
    public function isValidForVersion(int $currentVersion, ?DateTimeImmutable $now = null): bool
    {
        return $this->isValid($now) && $this->configVersion === $currentVersion;
    }

    /**
     * Check if consent was given for a specific category.
     */
    public function hasConsentFor(string $categorySlug): bool
    {
        return $this->preferences->hasConsentFor($categorySlug);
    }

    /**
     * Get all consented category slugs.
     *
     * @return array<string>
     */
    public function getConsentedCategories(): array
    {
        return $this->preferences->getConsentedCategories();
    }
}
