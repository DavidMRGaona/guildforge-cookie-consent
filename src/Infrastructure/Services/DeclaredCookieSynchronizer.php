<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Services;

use Illuminate\Support\Facades\Log;
use Modules\CookieConsent\Application\Services\CookieDeclarationRegistryInterface;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;
use Modules\CookieConsent\Domain\Entities\Cookie;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieRepositoryInterface;
use Modules\CookieConsent\Domain\ValueObjects\CookieId;

final readonly class DeclaredCookieSynchronizer implements DeclaredCookieSynchronizerInterface
{
    public function __construct(
        private CookieDeclarationRegistryInterface $registry,
        private CookieRepositoryInterface $cookies,
        private CookieCategoryRepositoryInterface $categories,
    ) {}

    public function sync(): int
    {
        $createdIds = [];

        foreach ($this->registry->all() as $declared) {
            if ($this->cookies->findByName($declared->name) !== null) {
                continue;
            }

            $category = $this->categories->findBySlug($declared->categorySlug);

            if ($category === null) {
                Log::warning('Declared cookie skipped: its category does not exist', [
                    'cookie' => $declared->name,
                    'category' => $declared->categorySlug,
                ]);

                continue;
            }

            $id = CookieId::generate();

            $this->cookies->save(new Cookie(
                id: $id,
                categoryId: $category->id(),
                name: $declared->name,
                provider: $declared->provider,
                purpose: $declared->purpose,
                type: $declared->type,
                duration: $declared->duration,
            ));

            $createdIds[] = $id->value();
        }

        if ($createdIds !== []) {
            // One event for the whole batch: visitors are asked again once, not per cookie
            event(new CookieConfigurationChanged(
                changeType: 'created',
                entityType: 'cookie',
                entityId: $createdIds[0],
            ));
        }

        return count($createdIds);
    }
}
