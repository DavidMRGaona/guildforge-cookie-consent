<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\CookieConsent\Domain\ValueObjects\VisitorId;

final class ConsentWithdrawn
{
    use Dispatchable;

    public function __construct(
        public readonly VisitorId $visitorId,
    ) {}
}
