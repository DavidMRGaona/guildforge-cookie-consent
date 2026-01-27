<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\CookieConsent\Domain\Enums\CookieType;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;

class CookiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cookies';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cookie-consent::cookie_consent.cookies.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.cookies.label');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.name'))
                    ->placeholder('_ga, _fbp, session_id...')
                    ->required()
                    ->maxLength(255),

                TextInput::make('provider')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.provider'))
                    ->placeholder('Google, Meta, Propio...')
                    ->required()
                    ->maxLength(255),

                TextInput::make('domain')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.domain'))
                    ->placeholder('.google.com, .facebook.com...')
                    ->maxLength(255),

                Select::make('type')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.type'))
                    ->options(CookieType::class)
                    ->default(CookieType::FirstParty->value)
                    ->required(),

                Textarea::make('purpose')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.purpose'))
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),

                TextInput::make('duration')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.duration'))
                    ->placeholder('Sesión, 1 año, 2 años...')
                    ->maxLength(100),

                Toggle::make('is_active')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.is_active'))
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('provider')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.provider'))
                    ->searchable(),

                TextColumn::make('domain')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.domain'))
                    ->placeholder('-'),

                TextColumn::make('type')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (CookieType $state): string => $state->getLabel())
                    ->color(fn (CookieType $state): string => match ($state) {
                        CookieType::FirstParty => 'success',
                        CookieType::ThirdParty => 'warning',
                    }),

                TextColumn::make('duration')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.duration'))
                    ->placeholder('-'),

                IconColumn::make('is_active')
                    ->label(__('cookie-consent::cookie_consent.cookies.fields.is_active'))
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'created',
                            entityType: 'cookie',
                            entityId: $record->id,
                        ));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'updated',
                            entityType: 'cookie',
                            entityId: $record->id,
                        ));
                    }),
                DeleteAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'deleted',
                            entityType: 'cookie',
                            entityId: $record->id,
                        ));
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
