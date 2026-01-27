<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources;

use App\Filament\Resources\BaseResource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;

class CookieConsentResource extends BaseResource
{
    protected static ?string $model = CookieConsentModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('cookie-consent::cookie_consent.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('cookie-consent::cookie_consent.consents.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.consents.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.consents.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visitor_id')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.visitor_id'))
                    ->limit(8)
                    ->tooltip(fn (CookieConsentModel $record): string => $record->visitor_id)
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.user'))
                    ->placeholder('Anónimo')
                    ->searchable(),

                TextColumn::make('consent_method')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.consent_method'))
                    ->badge()
                    ->formatStateUsing(fn (ConsentMethod $state): string => $state->getLabel())
                    ->color(fn (ConsentMethod $state): string => match ($state) {
                        ConsentMethod::Banner => 'success',
                        ConsentMethod::SettingsPage => 'info',
                        ConsentMethod::Api => 'warning',
                    }),

                TextColumn::make('config_version')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.config_version'))
                    ->alignCenter(),

                TextColumn::make('preferences')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.preferences'))
                    ->getStateUsing(function (CookieConsentModel $record): string {
                        /** @var array<string, bool> $preferences */
                        $preferences = $record->preferences;
                        $accepted = array_keys(array_filter($preferences, fn (bool $v): bool => $v));

                        return implode(', ', $accepted);
                    })
                    ->wrap()
                    ->limit(50),

                TextColumn::make('consented_at')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.consented_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.expires_at'))
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_hash')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.ip_hash'))
                    ->limit(8)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('consent_method')
                    ->label(__('cookie-consent::cookie_consent.consents.fields.consent_method'))
                    ->options(ConsentMethod::class),

                Filter::make('authenticated')
                    ->label('Usuario autenticado')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('user_id'))
                    ->toggle(),

                Filter::make('consented_today')
                    ->label('Hoy')
                    ->query(fn (Builder $query): Builder => $query->whereDate('consented_at', today()))
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('consented_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCookieConsents::route('/'),
            'view' => Pages\ViewCookieConsent::route('/{record}'),
        ];
    }
}
