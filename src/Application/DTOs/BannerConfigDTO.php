<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\DTOs;

final readonly class BannerConfigDTO
{
    /**
     * @param  array{primary: string, secondary: string, background: string, text: string}  $colors
     * @param  array{title: string, description: string, acceptAll: string, rejectAll: string, settings: string, save: string, privacyLink: string, privacyUrl: string, required: string, showCookies: string, cookieColumn: string, providerColumn: string, durationColumn: string, cancel: string, close: string, preferences: string, saveError: string}  $texts
     * @param  array<string, string>  $consentModeDefaults
     */
    public function __construct(
        public string $position,
        public string $layout,
        public string $theme,
        public array $colors,
        public bool $showRejectAll,
        public bool $showSettingsButton,
        public int $validityDays,
        public bool $reconsentOnChange,
        public int $configVersion,
        public array $texts,
        public bool $gtmEnabled,
        public string $gtmContainerId,
        public bool $consentModeEnabled,
        // Without Consent Mode, the container waits until the visitor accepts this category
        public string $gtmCategory = 'analytics',
        // A small button that reopens the preferences once the visitor has chosen
        public bool $showFloatingButton = true,
        // The link that reopens them, for the footer menu
        public string $preferencesUrl = '/consentimiento-cookies/preferencias',
        // Every Consent Mode key with its value before the visitor chooses
        public array $consentModeDefaults = [],
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
            'showFloatingButton' => $this->showFloatingButton,
            'validityDays' => $this->validityDays,
            'reconsentOnChange' => $this->reconsentOnChange,
            'configVersion' => $this->configVersion,
            'preferencesUrl' => $this->preferencesUrl,
            'texts' => $this->texts,
            'gtmEnabled' => $this->gtmEnabled,
            'gtmContainerId' => $this->gtmContainerId,
            'gtmCategory' => $this->gtmCategory,
            'consentModeEnabled' => $this->consentModeEnabled,
            'consentModeDefaults' => $this->consentModeDefaults,
        ];
    }
}
