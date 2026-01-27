<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\CookieConsent\Application\DTOs\SaveConsentDTO;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Http\Requests\SaveConsentRequest;

final class ConsentApiController extends Controller
{
    public function __construct(
        private readonly ConsentServiceInterface $consentService,
        private readonly BannerConfigServiceInterface $bannerConfigService,
    ) {}

    /**
     * Get all cookie categories with their cookies.
     */
    public function categories(): JsonResponse
    {
        $categories = $this->consentService->getCategories();

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
        } catch (\Modules\CookieConsent\Domain\Exceptions\InvalidConsentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
