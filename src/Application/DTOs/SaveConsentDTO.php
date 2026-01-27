<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

use Modules\CookieConsent\Domain\Enums\ConsentMethod;

final readonly class SaveConsentDTO
{
    /**
     * @param  array<string, bool>  $preferences
     */
    public function __construct(
        public string $visitorId,
        public array $preferences,
        public int $configVersion,
        public ConsentMethod $consentMethod,
        public string $ipAddress,
        public string $userAgent,
        public ?string $userId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            visitorId: $data['visitor_id'],
            preferences: $data['preferences'],
            configVersion: $data['config_version'],
            consentMethod: ConsentMethod::from($data['consent_method'] ?? 'banner'),
            ipAddress: $data['ip_address'],
            userAgent: $data['user_agent'],
            userId: $data['user_id'] ?? null,
        );
    }
}
