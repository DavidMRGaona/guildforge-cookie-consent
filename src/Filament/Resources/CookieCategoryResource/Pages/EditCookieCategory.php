<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;

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
    protected static string $resource = CookieCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => $this->record->is_system),
        ];
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
