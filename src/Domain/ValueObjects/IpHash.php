<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * SHA256 hash of IP address for GDPR compliance.
 * We don't store raw IP addresses.
 */
final readonly class IpHash implements Stringable
{
    private const HASH_LENGTH = 64; // SHA256 produces 64 hex characters

    public function __construct(
        public string $value,
    ) {
        $this->validate($value);
    }

    /**
     * Create hash from raw IP address.
     */
    public static function fromIp(string $ipAddress, string $salt = ''): self
    {
        $hash = hash('sha256', $ipAddress . $salt);

        return new self($hash);
    }

    /**
     * Create from existing hash value.
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(string $value): void
    {
        if (strlen($value) !== self::HASH_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('IP hash must be %d characters, got %d', self::HASH_LENGTH, strlen($value))
            );
        }

        if (! ctype_xdigit($value)) {
            throw new InvalidArgumentException('IP hash must contain only hexadecimal characters');
        }
    }
}
