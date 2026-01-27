<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use App\Application\Services\SettingsServiceInterface;
use App\Domain\ValueObjects\UserId;
use DateTimeImmutable;
use Modules\CookieConsent\Application\DTOs\CategoryDTO;
use Modules\CookieConsent\Application\DTOs\ConsentDTO;
use Modules\CookieConsent\Application\DTOs\CookieDTO;
use Modules\CookieConsent\Application\DTOs\SaveConsentDTO;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Domain\Entities\CookieConsent;
use Modules\CookieConsent\Domain\Events\ConsentGranted;
use Modules\CookieConsent\Domain\Events\ConsentUpdated;
use Modules\CookieConsent\Domain\Exceptions\InvalidConsentException;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieConsentRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\ConsentId;
use Modules\CookieConsent\Domain\ValueObjects\ConsentPreferences;
use Modules\CookieConsent\Domain\ValueObjects\IpHash;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;

final class ConsentService implements ConsentServiceInterface
{
    private const SETTINGS_VERSION_KEY = 'cookie-consent.config_version';

    public function __construct(
        private readonly CookieCategoryRepositoryInterface $categoryRepository,
        private readonly CookieRepositoryInterface $cookieRepository,
        private readonly CookieConsentRepositoryInterface $consentRepository,
        private readonly SettingsServiceInterface $settingsService,
    ) {}

    /**
     * @return array<CategoryDTO>
     */
    public function getCategories(): array
    {
        $categories = $this->categoryRepository->findAll();
        $result = [];

        foreach ($categories as $category) {
            $cookies = $this->cookieRepository->findByCategory($category->id());
            $cookieDTOs = array_map(
                fn ($cookie) => CookieDTO::fromEntity($cookie),
                array_filter($cookies, fn ($cookie) => $cookie->isActive())
            );

            $result[] = CategoryDTO::fromEntity($category, array_values($cookieDTOs));
        }

        return $result;
    }

    public function saveConsent(SaveConsentDTO $dto): ConsentDTO
    {
        // Validate required categories are accepted
        $requiredCategories = $this->categoryRepository->findRequired();
        foreach ($requiredCategories as $category) {
            if (! ($dto->preferences[$category->slug()] ?? false)) {
                throw InvalidConsentException::missingRequiredCategory($category->slug());
            }
        }

        // Check for existing consent
        $visitorId = VisitorId::fromString($dto->visitorId);
        $existingConsent = $this->consentRepository->findLatestByVisitor($visitorId);

        // Calculate expiry date
        $validityDays = (int) $this->settingsService->get('cookie-consent.consent_validity_days', 365);
        $consentedAt = new DateTimeImmutable;
        $expiresAt = $consentedAt->modify("+{$validityDays} days");

        // Create new consent
        $consent = new CookieConsent(
            id: ConsentId::generate(),
            visitorId: $visitorId,
            ipHash: IpHash::fromIp($dto->ipAddress, (string) config('app.key')),
            userAgent: $dto->userAgent,
            preferences: ConsentPreferences::fromArray($dto->preferences),
            configVersion: $dto->configVersion,
            consentMethod: $dto->consentMethod,
            consentedAt: $consentedAt,
            expiresAt: $expiresAt,
            userId: $dto->userId !== null ? UserId::fromString($dto->userId) : null,
        );

        $this->consentRepository->save($consent);

        // Dispatch appropriate event
        if ($existingConsent !== null) {
            event(new ConsentUpdated($consent, $existingConsent));
        } else {
            event(new ConsentGranted($consent));
        }

        return ConsentDTO::fromEntity($consent);
    }

    public function getConsent(VisitorId $visitorId): ?ConsentDTO
    {
        $consent = $this->consentRepository->findLatestByVisitor($visitorId);

        if ($consent === null) {
            return null;
        }

        return ConsentDTO::fromEntity($consent);
    }

    public function hasConsentFor(string $visitorId, string $categorySlug): bool
    {
        $consent = $this->consentRepository->findLatestByVisitor(
            VisitorId::fromString($visitorId)
        );

        if ($consent === null || ! $consent->isValid()) {
            // Check if it's a required category
            $category = $this->categoryRepository->findBySlug($categorySlug);

            return $category !== null && $category->isRequired();
        }

        return $consent->hasConsentFor($categorySlug);
    }

    public function getConfigVersion(): int
    {
        return (int) $this->settingsService->get(self::SETTINGS_VERSION_KEY, 1);
    }

    public function incrementConfigVersion(): void
    {
        $currentVersion = $this->getConfigVersion();
        $this->settingsService->set(self::SETTINGS_VERSION_KEY, (string) ($currentVersion + 1));
    }
}
