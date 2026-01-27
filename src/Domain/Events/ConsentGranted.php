<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\CookieConsent\Domain\Entities\CookieConsent;

final class ConsentGranted
{
    use Dispatchable;

    public function __construct(
        public readonly CookieConsent $consent,
    ) {}
}
