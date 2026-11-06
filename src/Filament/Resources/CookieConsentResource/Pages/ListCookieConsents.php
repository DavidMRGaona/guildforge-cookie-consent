<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Resources\Pages\ListRecords;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;

class ListCookieConsents extends ListRecords
{
    use HasKnowledgeGuideAction;

    protected static string $resource = CookieConsentResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->knowledgeGuideAction('cookie-consent.panel.consents'),
        ]);
    }
}
