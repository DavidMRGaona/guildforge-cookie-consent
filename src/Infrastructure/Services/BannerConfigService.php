<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use App\Application\Services\SettingsServiceInterface;
use Modules\CookieConsent\Application\DTOs\BannerConfigDTO;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;

final class BannerConfigService implements BannerConfigServiceInterface
{
    public function __construct(
        private readonly SettingsServiceInterface $settingsService,
        private readonly ConsentServiceInterface $consentService,
    ) {}

    public function getBannerConfig(): BannerConfigDTO
    {
        return new BannerConfigDTO(
            position: $this->settingsService->get('cookie-consent.banner_position', 'bottom'),
            layout: $this->settingsService->get('cookie-consent.banner_layout', 'bar'),
            theme: $this->settingsService->get('cookie-consent.banner_theme', 'light'),
            colors: [
                'primary' => $this->settingsService->get('cookie-consent.primary_color', '#10B981'),
                'secondary' => $this->settingsService->get('cookie-consent.secondary_color', '#6B7280'),
                'background' => $this->settingsService->get('cookie-consent.background_color', '#FFFFFF'),
                'text' => $this->settingsService->get('cookie-consent.text_color', '#1F2937'),
            ],
            showRejectAll: (bool) $this->settingsService->get('cookie-consent.show_reject_all', true),
            showSettingsButton: (bool) $this->settingsService->get('cookie-consent.show_settings_button', true),
            showLogo: (bool) $this->settingsService->get('cookie-consent.show_logo', true),
            blockPageUntilConsent: (bool) $this->settingsService->get('cookie-consent.block_page_until_consent', false),
            validityDays: (int) $this->settingsService->get('cookie-consent.consent_validity_days', 365),
            reconsentOnChange: (bool) $this->settingsService->get('cookie-consent.reconsent_on_change', true),
            configVersion: $this->consentService->getConfigVersion(),
            texts: [
                'title' => $this->settingsService->get('cookie-consent.banner_title', __('cookie-consent::cookie_consent.frontend.default_title')),
                'description' => $this->settingsService->get('cookie-consent.banner_description', __('cookie-consent::cookie_consent.frontend.default_description')),
                'acceptAll' => $this->settingsService->get('cookie-consent.accept_all_text', __('cookie-consent::cookie_consent.frontend.accept_all')),
                'rejectAll' => $this->settingsService->get('cookie-consent.reject_all_text', __('cookie-consent::cookie_consent.frontend.reject_all')),
                'settings' => $this->settingsService->get('cookie-consent.settings_text', __('cookie-consent::cookie_consent.frontend.settings')),
                'save' => $this->settingsService->get('cookie-consent.save_settings_text', __('cookie-consent::cookie_consent.frontend.save_preferences')),
                'privacyLink' => $this->settingsService->get('cookie-consent.privacy_policy_link_text', __('cookie-consent::cookie_consent.frontend.privacy_policy')),
                'privacyUrl' => $this->settingsService->get('cookie-consent.privacy_policy_url', '/politica-de-privacidad'),
            ],
            gtmEnabled: (bool) $this->settingsService->get('cookie-consent.gtm_enabled', false),
            gtmContainerId: $this->settingsService->get('cookie-consent.gtm_container_id', ''),
            consentModeEnabled: (bool) $this->settingsService->get('cookie-consent.consent_mode_enabled', true),
        );
    }

    public function getConsentValidityDays(): int
    {
        return (int) $this->settingsService->get('cookie-consent.consent_validity_days', 365);
    }
}
