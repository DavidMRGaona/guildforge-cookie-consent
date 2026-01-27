<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Application\Services\ScriptInjectorServiceInterface;
use Modules\CookieConsent\Domain\Repositories\CookieScriptRepositoryInterface;

final class ScriptInjectorService implements ScriptInjectorServiceInterface
{
    public function __construct(
        private readonly CookieScriptRepositoryInterface $scriptRepository,
        private readonly ConsentModeServiceInterface $consentModeService,
        private readonly BannerConfigServiceInterface $bannerConfigService,
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

        // Always add consent mode defaults first (if enabled)
        if ($config->consentModeEnabled) {
            $scripts[] = $this->consentModeService->generateDefaultScript();
        }

        // Add GTM script if enabled and analytics/marketing consent exists
        if ($config->gtmEnabled && $config->gtmContainerId !== '') {
            $scripts[] = $this->getGtmHeadScript($config->gtmContainerId);
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

        // Add GTM noscript fallback
        if ($config->gtmEnabled && $config->gtmContainerId !== '') {
            $scripts[] = $this->getGtmBodyStartScript($config->gtmContainerId);
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

    private function getGtmHeadScript(string $containerId): string
    {
        return <<<SCRIPT
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$containerId}');</script>
<!-- End Google Tag Manager -->
SCRIPT;
    }

    private function getGtmBodyStartScript(string $containerId): string
    {
        return <<<SCRIPT
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$containerId}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
SCRIPT;
    }
}
