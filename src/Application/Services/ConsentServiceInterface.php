<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

use Modules\CookieConsent\Application\DTOs\CategoryDTO;
use Modules\CookieConsent\Application\DTOs\ConsentDTO;
use Modules\CookieConsent\Application\DTOs\SaveConsentDTO;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;

interface ConsentServiceInterface
{
    /**
     * Get all categories with their cookies for display.
     *
     * @return array<CategoryDTO>
     */
    public function getCategories(): array;

    /**
     * Save a consent record.
     */
    public function saveConsent(SaveConsentDTO $dto): ConsentDTO;

    /**
     * Get the latest consent for a visitor.
     */
    public function getConsent(VisitorId $visitorId): ?ConsentDTO;

    /**
     * Check if a visitor has consent for a specific category.
     */
    public function hasConsentFor(string $visitorId, string $categorySlug): bool;

    /**
     * Get the current config version.
     */
    public function getConfigVersion(): int;

    /**
     * Increment the config version (called when categories/cookies/scripts change).
     */
    public function incrementConfigVersion(): void;
}
