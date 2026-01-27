<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

class CreateCookieCategory extends CreateRecord
{
    protected static string $resource = CookieCategoryResource::class;

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
