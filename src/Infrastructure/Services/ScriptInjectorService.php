<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Modules\CookieConsent\Application\DTOs\BannerConfigDTO;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Application\Services\ScriptInjectorServiceInterface;
use Modules\CookieConsent\Domain\Repositories\CookieScriptRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\GtmContainerId;

final readonly class ScriptInjectorService implements ScriptInjectorServiceInterface
{
    public function __construct(
        private CookieScriptRepositoryInterface $scriptRepository,
        private ConsentModeServiceInterface $consentModeService,
        private BannerConfigServiceInterface $bannerConfigService,
    ) {}

    /**
     * @param  array<string>  $consentedCategories
     * @return array{head: string, body_start: string, body_end: string, noscript: string}
     */
    public function getScriptsForConsent(array $consentedCategories): array
    {
        return [
            'head' => $this->getHeadScripts($consentedCategories),
            'body_start' => $this->getBodyStartScripts($consentedCategories),
            'body_end' => $this->getBodyEndScripts($consentedCategories),
            'noscript' => $this->getNoscriptContent($consentedCategories),
        ];
    }

    /**
     * @param  array<string>  $consentedCategories
     */
    public function getHeadScripts(array $consentedCategories): string
    {
        $config = $this->bannerConfigService->getBannerConfig();
        $scripts = [];

        // Always add consent mode defaults first (if enabled), then the visitor's
        // stored choices: the banner only updates Consent Mode when they are made
        if ($config->consentModeEnabled) {
            $scripts[] = $this->consentModeService->generateDefaultScript();

            if (count($consentedCategories) > 0) {
                $scripts[] = $this->consentModeService->generateUpdateScript(
                    array_fill_keys($consentedCategories, true)
                );
            }
        }

        // With Consent Mode the container loads on every page and follows the consent
        // signals above; without it, only once the visitor accepts its category
        $containerId = $this->containerId($config);
        if ($containerId instanceof GtmContainerId
            && ($config->consentModeEnabled || in_array($config->gtmCategory, $consentedCategories, true))) {
            $scripts[] = $this->getGtmHeadScript($containerId);
        }

        // Add category-specific head scripts
        if (count($consentedCategories) > 0) {
            $categoryScripts = $this->scriptRepository->findActiveByCategorySlugs($consentedCategories);

            foreach ($categoryScripts as $script) {
                if ($script->hasHeadScript()) {
                    $scripts[] = $script->scriptHead();
                }
            }
        }

        return implode("\n", $scripts);
    }

    /**
     * @param  array<string>  $consentedCategories
     */
    public function getBodyStartScripts(array $consentedCategories): string
    {
        $config = $this->bannerConfigService->getBannerConfig();
        $scripts = [];

        // The noscript container gets no consent signals, so even with Consent Mode it
        // waits for its category
        $containerId = $this->containerId($config);
        if ($containerId instanceof GtmContainerId && in_array($config->gtmCategory, $consentedCategories, true)) {
            $scripts[] = $this->getGtmBodyStartScript($containerId);
        }

        // Add category-specific body start scripts
        if (count($consentedCategories) > 0) {
            $categoryScripts = $this->scriptRepository->findActiveByCategorySlugs($consentedCategories);

            foreach ($categoryScripts as $script) {
                if ($script->hasBodyStartScript()) {
                    $scripts[] = $script->scriptBodyStart();
                }
            }
        }

        return implode("\n", $scripts);
    }

    /**
     * @param  array<string>  $consentedCategories
     */
    public function getBodyEndScripts(array $consentedCategories): string
    {
        $scripts = [];

        if (count($consentedCategories) > 0) {
            $categoryScripts = $this->scriptRepository->findActiveByCategorySlugs($consentedCategories);

            foreach ($categoryScripts as $script) {
                if ($script->hasBodyEndScript()) {
                    $scripts[] = $script->scriptBodyEnd();
                }
            }
        }

        return implode("\n", $scripts);
    }

    /**
     * @param  array<string>  $consentedCategories
     */
    public function getNoscriptContent(array $consentedCategories): string
    {
        $scripts = [];

        if (count($consentedCategories) > 0) {
            $categoryScripts = $this->scriptRepository->findActiveByCategorySlugs($consentedCategories);

            foreach ($categoryScripts as $script) {
                if ($script->hasNoscriptContent()) {
                    $scripts[] = $script->noscriptContent();
                }
            }
        }

        return implode("\n", $scripts);
    }

    /**
     * Null when the container is off or its ID is malformed: it goes into the page as it is.
     */
    private function containerId(BannerConfigDTO $config): ?GtmContainerId
    {
        return $config->gtmEnabled ? GtmContainerId::tryFrom(trim($config->gtmContainerId)) : null;
    }

    private function getGtmHeadScript(GtmContainerId $containerId): string
    {
        return <<<SCRIPT
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$containerId->value}');</script>
<!-- End Google Tag Manager -->
SCRIPT;
    }

    private function getGtmBodyStartScript(GtmContainerId $containerId): string
    {
        return <<<SCRIPT
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$containerId->value}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
SCRIPT;
    }
}
