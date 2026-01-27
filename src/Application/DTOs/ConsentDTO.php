<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Entities\CookieConsent;

final readonly class ConsentDTO
{
    /**
     * @param  array<string, bool>  $preferences
     * @param  array<string>  $consentedCategories
     */
    public function __construct(
        public string $id,
        public string $visitorId,
        public ?string $userId,
        public array $preferences,
        public array $consentedCategories,
        public int $configVersion,
        public string $consentMethod,
        public string $consentedAt,
        public string $expiresAt,
        public bool $isValid,
    ) {}

    public static function fromEntity(CookieConsent $consent): self
    {
        return new self(
            id: $consent->id()->value(),
            visitorId: $consent->visitorId()->value(),
            userId: $consent->userId()?->value(),
            preferences: $consent->preferences()->toArray(),
            consentedCategories: $consent->getConsentedCategories(),
            configVersion: $consent->configVersion(),
            consentMethod: $consent->consentMethod()->value,
            consentedAt: $consent->consentedAt()->format('c'),
            expiresAt: $consent->expiresAt()->format('c'),
            isValid: $consent->isValid(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'visitorId' => $this->visitorId,
            'userId' => $this->userId,
            'preferences' => $this->preferences,
            'consentedCategories' => $this->consentedCategories,
            'configVersion' => $this->configVersion,
            'consentMethod' => $this->consentMethod,
            'consentedAt' => $this->consentedAt,
            'expiresAt' => $this->expiresAt,
            'isValid' => $this->isValid,
        ];
    }
}
