<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Exceptions;

use Exception;
use Modules\CookieConsent\Domain\ValueObjects\CategoryId;

final class CategoryNotFoundException extends Exception
{
    public static function withId(CategoryId $id): self
    {
        return new self("Cookie category with ID '{$id->value()}' not found");
    }

    public static function withSlug(string $slug): self
    {
        return new self("Cookie category with slug '{$slug}' not found");
    }
}
