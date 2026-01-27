<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;

class ListCookieConsents extends ListRecords
{
    protected static string $resource = CookieConsentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
