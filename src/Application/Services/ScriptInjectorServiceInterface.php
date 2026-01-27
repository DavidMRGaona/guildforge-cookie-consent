<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

interface ScriptInjectorServiceInterface
{
    /**
     * Get all scripts to inject based on consented categories.
     * Returns scripts grouped by position for the middleware.
     *
     * @param  array<string>  $consentedCategories
     * @return array{head: string, body_start: string, body_end: string, noscript: string}
     */
    public function getScriptsForConsent(array $consentedCategories): array;

    /**
     * Generate HTML for <head> scripts (Google Consent Mode defaults + consented scripts).
     *
     * @param  array<string>  $consentedCategories
     */
    public function getHeadScripts(array $consentedCategories): string;

    /**
     * Generate HTML for scripts just after <body> (e.g., GTM noscript).
     *
     * @param  array<string>  $consentedCategories
     */
    public function getBodyStartScripts(array $consentedCategories): string;

    /**
     * Generate HTML for scripts before </body>.
     *
     * @param  array<string>  $consentedCategories
     */
    public function getBodyEndScripts(array $consentedCategories): string;

    /**
     * Generate <noscript> content for consented scripts.
     *
     * @param  array<string>  $consentedCategories
     */
    public function getNoscriptContent(array $consentedCategories): string;
}
