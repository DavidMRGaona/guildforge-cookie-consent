<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;

class ListCookieCategories extends ListRecords
{
    use HasKnowledgeGuideAction;

    protected static string $resource = CookieCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            CreateAction::make(),
            $this->knowledgeGuideAction('cookie-consent.panel.categories'),
        ]);
    }
}
