<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\CookieConsent\Application\DTOs\SaveConsentDTO;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Application\Services\VisibleCategoriesServiceInterface;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Domain\Exceptions\InvalidConsentException;
use Modules\CookieConsent\Http\Requests\SaveConsentRequest;

final class ConsentApiController extends Controller
{
    /** Read by the banner (CookieBanner.vue), which then opens the preferences. */
    public const string PREFERENCES_ANCHOR = 'preferencias-cookies';

    public function __construct(
        private readonly ConsentServiceInterface $consentService,
        private readonly BannerConfigServiceInterface $bannerConfigService,
    ) {}

    /**
     * The categories the banner offers, with their cookies.
     */
    public function categories(VisibleCategoriesServiceInterface $visibleCategories): JsonResponse
    {
        $categories = $visibleCategories->getVisibleCategories();

        return response()->json([
            'data' => array_map(fn ($category) => $category->toArray(), $categories),
        ]);
    }

    /**
     * Get banner configuration.
     */
    public function config(): JsonResponse
    {
        $config = $this->bannerConfigService->getBannerConfig();

        return response()->json([
            'data' => $config->toArray(),
        ]);
    }

    /**
     * The link to the preferences, opened directly (a new tab, no JavaScript yet): the
     * banner opens them on the home page when it sees the anchor.
     */
    public function preferences(): RedirectResponse
    {
        return redirect()->to(url('/').'#'.self::PREFERENCES_ANCHOR);
    }

    /**
     * Store consent preferences.
     */
    public function store(SaveConsentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new SaveConsentDTO(
            visitorId: $validated['visitor_id'],
            preferences: $validated['preferences'],
            configVersion: $validated['config_version'],
            consentMethod: ConsentMethod::from($validated['consent_method'] ?? 'banner'),
            ipAddress: $request->ip() ?? '0.0.0.0',
            userAgent: $request->userAgent() ?? 'Unknown',
            userId: auth()->id() !== null ? (string) auth()->id() : null,
        );

        try {
            $consent = $this->consentService->saveConsent($dto);

            return response()->json([
                'data' => $consent->toArray(),
                'message' => 'Consent saved successfully',
            ], 201);
        } catch (InvalidConsentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
