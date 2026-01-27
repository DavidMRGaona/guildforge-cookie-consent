<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers;

use Filament\Forms\Components\Section;
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
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;

class ScriptsRelationManager extends RelationManager
{
    protected static string $relationship = 'scripts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('cookie-consent::cookie_consent.scripts.plural_label');
    }

    public static function getModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.scripts.label');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.name'))
                            ->placeholder('Google Analytics, GTM, Meta Pixel...')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.description'))
                            ->placeholder('Notas internas sobre este script...')
                            ->rows(2),

                        TextInput::make('sort_order')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Scripts')
                    ->description('Introduce el código de los scripts. No incluyas las etiquetas <script> a menos que sea necesario.')
                    ->schema([
                        Textarea::make('script_head')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.script_head'))
                            ->helperText(__('cookie-consent::cookie_consent.scripts.hints.script_head'))
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('script_body_start')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.script_body_start'))
                            ->helperText(__('cookie-consent::cookie_consent.scripts.hints.script_body_start'))
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('script_body_end')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.script_body_end'))
                            ->helperText(__('cookie-consent::cookie_consent.scripts.hints.script_body_end'))
                            ->rows(5)
                            ->columnSpanFull(),

                        Textarea::make('noscript_content')
                            ->label(__('cookie-consent::cookie_consent.scripts.fields.noscript_content'))
                            ->helperText(__('cookie-consent::cookie_consent.scripts.hints.noscript_content'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('cookie-consent::cookie_consent.scripts.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('cookie-consent::cookie_consent.scripts.fields.description'))
                    ->limit(50)
                    ->placeholder('-'),

                TextColumn::make('sort_order')
                    ->label(__('cookie-consent::cookie_consent.scripts.fields.sort_order'))
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label(__('cookie-consent::cookie_consent.scripts.fields.is_active'))
                    ->boolean(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'created',
                            entityType: 'script',
                            entityId: $record->id,
                        ));
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'updated',
                            entityType: 'script',
                            entityId: $record->id,
                        ));
                    }),
                DeleteAction::make()
                    ->after(function ($record) {
                        event(new CookieConfigurationChanged(
                            changeType: 'deleted',
                            entityType: 'script',
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
