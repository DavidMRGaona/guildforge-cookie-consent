<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Exceptions;

use Exception;

final class InvalidConsentException extends Exception
{
    public static function expired(): self
    {
        return new self('Consent has expired');
    }

    public static function invalidVersion(int $expected, int $actual): self
    {
        return new self("Consent version mismatch: expected {$expected}, got {$actual}");
    }

    public static function missingRequiredCategory(string $categorySlug): self
    {
        return new self("Required category '{$categorySlug}' must be accepted");
    }
}
