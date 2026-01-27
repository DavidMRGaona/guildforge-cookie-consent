<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

final readonly class BannerConfigDTO
{
    /**
     * @param  array{primary: string, secondary: string, background: string, text: string}  $colors
     * @param  array{title: string, description: string, acceptAll: string, rejectAll: string, settings: string, save: string, privacyLink: string, privacyUrl: string}  $texts
     */
    public function __construct(
        public string $position,
        public string $layout,
        public string $theme,
        public array $colors,
        public bool $showRejectAll,
        public bool $showSettingsButton,
        public bool $showLogo,
        public bool $blockPageUntilConsent,
        public int $validityDays,
        public bool $reconsentOnChange,
        public int $configVersion,
        public array $texts,
        public bool $gtmEnabled,
        public string $gtmContainerId,
        public bool $consentModeEnabled,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'layout' => $this->layout,
            'theme' => $this->theme,
            'colors' => $this->colors,
            'showRejectAll' => $this->showRejectAll,
            'showSettingsButton' => $this->showSettingsButton,
            'showLogo' => $this->showLogo,
            'blockPageUntilConsent' => $this->blockPageUntilConsent,
            'validityDays' => $this->validityDays,
            'reconsentOnChange' => $this->reconsentOnChange,
            'configVersion' => $this->configVersion,
            'texts' => $this->texts,
            'gtmEnabled' => $this->gtmEnabled,
            'gtmContainerId' => $this->gtmContainerId,
            'consentModeEnabled' => $this->consentModeEnabled,
        ];
    }
}
