<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Resources\Pages\CreateRecord;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

class CreateCookieCategory extends CreateRecord
{
    use HasKnowledgeGuideAction;

    protected static string $resource = CookieCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->knowledgeGuideAction('cookie-consent.panel.categories'),
        ]);
    }

    protected function afterCreate(): void
    {
        /** @var CookieCategoryModel $record */
        $record = $this->record;

        event(new CookieConfigurationChanged(
            changeType: 'created',
            entityType: 'category',
            entityId: $record->id,
        ));
    }
}
