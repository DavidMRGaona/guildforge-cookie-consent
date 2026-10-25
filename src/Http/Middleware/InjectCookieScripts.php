<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Http\Middleware;

use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ScriptInjectorServiceInterface;
use Modules\CookieConsent\Domain\ValueObjects\ConsentPreferences;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class InjectCookieScripts
{
    /**
     * Written by the banner in the browser, so it is never encrypted.
     */
    public const string CONSENT_COOKIE_NAME = 'guildforge_cookie_consent';

    public function __construct(
        private ScriptInjectorServiceInterface $scriptInjector,
        private BannerConfigServiceInterface $bannerConfigService,
    ) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        // Only process HTML responses
        if (! $response instanceof Response) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type') ?? '';
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        // Get consented categories from cookie
        $consentedCategories = $this->getConsentedCategoriesFromCookie($request);

        // Get scripts for the consented categories
        $scripts = $this->scriptInjector->getScriptsForConsent($consentedCategories);

        // Inject scripts into HTML
        $content = $response->getContent();
        if ($content !== false) {
            $content = $this->injectScripts($content, $scripts);
            $response->setContent($content);
        }

        return $response;
    }

    /**
     * @return array<string>
     */
    private function getConsentedCategoriesFromCookie(Request $request): array
    {
        $cookieValue = $request->cookie(self::CONSENT_COOKIE_NAME);

        if ($cookieValue === null || $cookieValue === '' || ! is_string($cookieValue)) {
            return [];
        }

        try {
            $data = json_decode($cookieValue, true);
            if (! is_array($data) || ! isset($data['preferences']) || ! $this->isStillValid($data)) {
                return [];
            }

            $preferences = ConsentPreferences::fromArray($data['preferences']);

            return $preferences->getConsentedCategories();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * The banner asks again once the consent expires or, when configured, once the
     * configuration changes: until then the old choices must not apply either.
     *
     * @param  array<mixed>  $data
     */
    private function isStillValid(array $data): bool
    {
        if (! is_string($data['timestamp'] ?? null) || ! is_int($data['version'] ?? null)) {
            return false;
        }

        try {
            $consentedAt = new DateTimeImmutable($data['timestamp']);
        } catch (\Exception) {
            return false;
        }

        $config = $this->bannerConfigService->getBannerConfig();

        if ($consentedAt->modify("+{$config->validityDays} days") < new DateTimeImmutable) {
            return false;
        }

        return ! $config->reconsentOnChange || $data['version'] === $config->configVersion;
    }

    /**
     * @param  array{head: string, body_start: string, body_end: string, noscript: string}  $scripts
     */
    private function injectScripts(string $content, array $scripts): string
    {
        // Inject head scripts before </head>
        if ($scripts['head'] !== '') {
            $content = str_replace(
                '</head>',
                $scripts['head']."\n</head>",
                $content
            );
        }

        // Inject body start scripts after <body...>
        if ($scripts['body_start'] !== '') {
            $content = preg_replace(
                '/(<body[^>]*>)/i',
                '$1'."\n".$scripts['body_start'],
                $content,
                1
            ) ?? $content;
        }

        // Inject body end scripts before </body>
        if ($scripts['body_end'] !== '') {
            return str_replace(
                '</body>',
                $scripts['body_end']."\n</body>",
                $content
            );
        }

        return $content;
    }
}
