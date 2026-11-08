<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Application\Services;

use Modules\CookieConsent\Application\DTOs\CategoryDTO;

/**
 * The categories visitors are asked about: required ones and those that change something
 * when accepted or withdrawn.
 */
interface VisibleCategoriesServiceInterface
{
    /**
     * Required categories and optional ones with an active cookie, an active script, the
     * Google Tag Manager container or a Consent Mode signal that something on the page
     * reads, in their order.
     *
     * @return list<CategoryDTO>
     */
    public function getVisibleCategories(): array;
}
