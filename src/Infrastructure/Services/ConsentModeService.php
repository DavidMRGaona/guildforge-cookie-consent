<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Domain\Enums\ConsentModeKey;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;

final class ConsentModeService implements ConsentModeServiceInterface
{
    public function __construct(
        private readonly CookieCategoryRepositoryInterface $categoryRepository,
    ) {}

    public function generateDefaultScript(): string
    {
        $defaults = [];
        foreach (ConsentModeKey::cases() as $key) {
            $defaults[$key->value] = $key->defaultValue();
        }

        $defaultsJson = json_encode($defaults, JSON_PRETTY_PRINT);

        return <<<SCRIPT
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}

// Set default consent state (denied for all except security_storage)
gtag('consent', 'default', {$defaultsJson});
</script>
SCRIPT;
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    public function generateUpdateScript(array $preferences): string
    {
        $consentModeValues = $this->mapToConsentMode($preferences);
        $valuesJson = json_encode($consentModeValues, JSON_PRETTY_PRINT);

        return <<<SCRIPT
<script>
gtag('consent', 'update', {$valuesJson});
</script>
SCRIPT;
    }

    /**
     * @param  array<string, bool>  $preferences
     * @return array<string, string>
     */
    public function mapToConsentMode(array $preferences): array
    {
        $result = [];

        // Initialize all keys as denied
        foreach (ConsentModeKey::cases() as $key) {
            $result[$key->value] = $key->defaultValue();
        }

        // Get all categories to map their consent mode keys
        $categories = $this->categoryRepository->findAll();

        foreach ($categories as $category) {
            $hasConsent = $preferences[$category->slug()] ?? $category->isRequired();

            if ($hasConsent) {
                foreach ($category->consentModeKeys() as $consentModeKey) {
                    $result[$consentModeKey->value] = 'granted';
                }
            }
        }

        return $result;
    }
}
