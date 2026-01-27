<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use App\Filament\Resources\BaseResource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\CookieConsent\Domain\Enums\ConsentModeKey;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

class CookieCategoryResource extends BaseResource
{
    protected static ?string $model = CookieCategoryModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('cookie-consent::cookie_consent.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('cookie-consent::cookie_consent.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.categories.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cookie-consent::cookie_consent.categories.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?string $old) {
                                if (blank($state)) {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),

                        TextInput::make('slug')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Textarea::make('description')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.description'))
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_required')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.is_required'))
                            ->helperText('Las categorías requeridas no pueden ser rechazadas por el usuario')
                            ->disabled(fn (?CookieCategoryModel $record): bool => $record !== null && $record->is_required),

                        TextInput::make('sort_order')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Google Consent Mode')
                    ->description('Selecciona las claves de Google Consent Mode v2 asociadas a esta categoría')
                    ->schema([
                        CheckboxList::make('consent_mode_keys')
                            ->label(__('cookie-consent::cookie_consent.categories.fields.consent_mode_keys'))
                            ->options(
                                collect(ConsentModeKey::cases())
                                    ->mapWithKeys(fn (ConsentModeKey $key) => [
                                        $key->value => $key->getLabel(),
                                    ])
                                    ->all()
                            )
                            ->descriptions(
                                collect(ConsentModeKey::cases())
                                    ->mapWithKeys(fn (ConsentModeKey $key) => [
                                        $key->value => $key->description(),
                                    ])
                                    ->all()
                            )
                            ->columns(2),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.slug'))
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_required')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.is_required'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.sort_order'))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('cookies_count')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.cookies_count'))
                    ->counts('cookies')
                    ->alignCenter(),

                TextColumn::make('scripts_count')
                    ->label(__('cookie-consent::cookie_consent.categories.fields.scripts_count'))
                    ->counts('scripts')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (CookieCategoryModel $record): bool => $record->is_system),
            ])
            ->checkIfRecordIsSelectableUsing(fn (CookieCategoryModel $record): bool => ! $record->is_system)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->reject(fn (CookieCategoryModel $record): bool => $record->is_system)
                                ->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CookiesRelationManager::class,
            RelationManagers\ScriptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCookieCategories::route('/'),
            'create' => Pages\CreateCookieCategory::route('/create'),
            'edit' => Pages\EditCookieCategory::route('/{record}/edit'),
        ];
    }
}
