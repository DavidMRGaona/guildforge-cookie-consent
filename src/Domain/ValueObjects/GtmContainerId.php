<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\ValueObjects;

/**
 * A Google Tag Manager container ID ("GTM-XXXXXX"). It is written into the page's
 * JavaScript and markup, so anything else is never accepted.
 */
final readonly class GtmContainerId
{
    public const string PATTERN = '/^GTM-[A-Z0-9]+$/';

    private function __construct(
        public string $value,
    ) {}

    public static function tryFrom(string $value): ?self
    {
        return preg_match(self::PATTERN, $value) === 1 ? new self($value) : null;
    }
}
