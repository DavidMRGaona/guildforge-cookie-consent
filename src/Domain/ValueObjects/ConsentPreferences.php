<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Wrapper for consent preferences JSON.
 * Maps category slugs to boolean consent values.
 */
final readonly class ConsentPreferences implements JsonSerializable, Stringable
{
    /**
     * @param  array<string, bool>  $preferences
     */
    public function __construct(
        private array $preferences,
    ) {
        $this->validate($preferences);
    }

    /**
     * @param  array<string, bool>  $preferences
     */
    public static function fromArray(array $preferences): self
    {
        return new self($preferences);
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON for consent preferences');
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Consent preferences must be an array');
        }

        return new self($decoded);
    }

    /**
     * Check if consent was given for a specific category.
     */
    public function hasConsentFor(string $categorySlug): bool
    {
        return $this->preferences[$categorySlug] ?? false;
    }

    /**
     * Get all consented category slugs.
     *
     * @return array<string>
     */
    public function getConsentedCategories(): array
    {
        return array_keys(array_filter($this->preferences, fn (bool $value) => $value));
    }

    /**
     * Get all declined category slugs.
     *
     * @return array<string>
     */
    public function getDeclinedCategories(): array
    {
        return array_keys(array_filter($this->preferences, fn (bool $value) => ! $value));
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return $this->preferences;
    }

    public function toJson(): string
    {
        return (string) json_encode($this->preferences);
    }

    /**
     * @return array<string, bool>
     */
    public function jsonSerialize(): array
    {
        return $this->preferences;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function equals(self $other): bool
    {
        return $this->preferences === $other->preferences;
    }

    /**
     * @param  array<mixed, mixed>  $preferences
     */
    private function validate(array $preferences): void
    {
        foreach ($preferences as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Preference keys must be strings (category slugs)');
            }

            if (! is_bool($value)) {
                throw new InvalidArgumentException("Preference value for '{$key}' must be a boolean");
            }
        }
    }
}
