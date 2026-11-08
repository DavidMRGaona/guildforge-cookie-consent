<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Modules\CookieConsent\Application\DTOs\BannerConfigDTO;
use Modules\CookieConsent\Application\DTOs\CategoryDTO;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Application\Services\VisibleCategoriesServiceInterface;
use Modules\CookieConsent\Domain\ValueObjects\GtmContainerId;

final readonly class VisibleCategoriesService implements VisibleCategoriesServiceInterface
{
    public function __construct(
        private ConsentServiceInterface $consentService,
        private BannerConfigServiceInterface $bannerConfigService,
    ) {}

    public function getVisibleCategories(): array
    {
        $categories = $this->consentService->getCategories();
        $config = $this->bannerConfigService->getBannerConfig();
        $containerLoads = $config->gtmContainer() instanceof GtmContainerId;

        // Consent Mode signals only matter to a Google tag: the container or any script,
        // since a tag may sit in a category other than the one whose signal it reads
        $signalsAreRead = $config->consentModeEnabled
            && ($containerLoads || array_any($categories, fn (CategoryDTO $category): bool => $category->hasScripts));

        return array_values(array_filter(
            $categories,
            fn (CategoryDTO $category): bool => $this->isShown($category, $config, $containerLoads, $signalsAreRead),
        ));
    }

    private function isShown(CategoryDTO $category, BannerConfigDTO $config, bool $containerLoads, bool $signalsAreRead): bool
    {
        return $category->isRequired
            || $category->cookies !== []
            || $category->hasScripts
            || ($containerLoads && $category->slug === $config->gtmCategory)
            || ($signalsAreRead && $category->consentModeKeys !== []);
    }
}
