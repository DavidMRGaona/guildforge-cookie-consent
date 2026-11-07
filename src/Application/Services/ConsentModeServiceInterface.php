<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

interface ConsentModeServiceInterface
{
    /**
     * Every Consent Mode key with its value before the visitor chooses (all denied except
     * security_storage). The banner gets this list too, so both sides map from it.
     *
     * @return array<string, string>
     */
    public function defaults(): array;

    /**
     * Generate the default consent mode script (all denied except security_storage).
     */
    public function generateDefaultScript(): string;

    /**
     * Generate the consent mode update script based on user preferences.
     *
     * @param  array<string, bool>  $preferences  Map of category slug to consent value
     */
    public function generateUpdateScript(array $preferences): string;

    /**
     * Map category preferences to Google Consent Mode values.
     *
     * @param  array<string, bool>  $preferences  Map of category slug to consent value
     * @return array<string, string> Map of consent mode key to 'granted' or 'denied'
     */
    public function mapToConsentMode(array $preferences): array;
}
