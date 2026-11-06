<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;

use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

/**
 * @property-read CookieCategoryModel $record
 */
class EditCookieCategory extends EditRecord
{
    use HasKnowledgeGuideAction;

    protected static string $resource = CookieCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            DeleteAction::make()
                ->hidden(fn () => $this->record->is_system),
            $this->knowledgeGuideAction('cookie-consent.panel.categories'),
        ]);
    }

    protected function afterSave(): void
    {
        event(new CookieConfigurationChanged(
            changeType: 'updated',
            entityType: 'category',
            entityId: $this->record->id,
        ));
    }
}
