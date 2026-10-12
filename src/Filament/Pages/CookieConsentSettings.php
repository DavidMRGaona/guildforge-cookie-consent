<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Pages;

use App\Application\Services\SettingsServiceInterface;
use App\Filament\Concerns\ChecksPermissions;
use App\Filament\Concerns\SafeModuleNavigation;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Modules\CookieConsent\Domain\Enums\BannerLayout;
use Modules\CookieConsent\Domain\Enums\BannerPosition;
use Modules\CookieConsent\Domain\Enums\BannerTheme;

/**
 * Banner settings, stored as "cookie-consent.<key>" settings: the same place
 * BannerConfigService reads them from.
 *
 * @property \Filament\Schemas\Schema $form
 */
final class CookieConsentSettings extends Page implements HasForms
{
    use ChecksPermissions;
    use InteractsWithForms;
    use SafeModuleNavigation;

    private const PREFIX = 'cookie-consent.';

    /**
     * Settings edited on this page; any other "cookie-consent.*" setting
     * (config_version) is left as it is.
     *
     * @var list<string>
     */
    private const KEYS = [
        'banner_position',
        'banner_layout',
        'banner_theme',
        'primary_color',
        'secondary_color',
        'background_color',
        'text_color',
        'show_logo',
        'banner_title',
        'banner_description',
        'accept_all_text',
        'reject_all_text',
        'settings_text',
        'save_settings_text',
        'privacy_policy_link_text',
        'privacy_policy_url',
        'show_reject_all',
        'show_settings_button',
        'block_page_until_consent',
        'consent_validity_days',
        'reconsent_on_change',
        'gtm_enabled',
        'gtm_container_id',
        'consent_mode_enabled',
    ];

    /** @var list<string> */
    private const BOOLEAN_KEYS = [
        'show_logo',
        'show_reject_all',
        'show_settings_button',
        'block_page_until_consent',
        'reconsent_on_change',
        'gtm_enabled',
        'consent_mode_enabled',
    ];

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.simple-settings';

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public static function getNavigationGroup(): string
    {
        return __('cookie-consent::cookie_consent.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('cookie-consent::cookie_consent.settings.navigation');
    }

    public function getTitle(): string
    {
        return __('cookie-consent::cookie_consent.settings.title');
    }

    public static function canAccess(): bool
    {
        return self::userCan('settings.manage');
    }

    public function mount(SettingsServiceInterface $settings): void
    {
        // A setting never stored shows the module default (config/settings.php)
        /** @var array<string, mixed> $defaults */
        $defaults = config('modules.settings.cookie-consent', []);
        $state = [];

        foreach (self::KEYS as $key) {
            $value = $settings->get(self::PREFIX.$key, $defaults[$key] ?? null);

            $state[$key] = in_array($key, self::BOOLEAN_KEYS, true)
                ? filter_var($value, FILTER_VALIDATE_BOOLEAN)
                : $value;
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::getFormSchemaComponents())
            ->statePath('data');
    }

    public function save(SettingsServiceInterface $settings): void
    {
        // Hidden fields (custom colours, GTM container) are not in the state: their
        // stored values stay
        foreach ($this->form->getState() as $key => $value) {
            if (! in_array($key, self::KEYS, true)) {
                continue;
            }

            $settings->set(self::PREFIX.$key, match (true) {
                is_bool($value) => $value ? '1' : '0',
                $value instanceof BackedEnum => (string) $value->value,
                is_scalar($value) => (string) $value,
                default => '',
            });
        }

        Notification::make()
            ->title(__('cookie-consent::cookie_consent.settings.saved'))
            ->success()
            ->send();
    }

    /**
     * @return array<\Filament\Schemas\Components\Component>
     */
    public static function getFormSchemaComponents(): array
    {
        return [
            Section::make(__('cookie-consent::cookie_consent.settings.appearance'))
                ->schema([
                    Select::make('banner_position')
                        ->label(__('cookie-consent::cookie_consent.settings.banner_position'))
                        ->options(BannerPosition::class)
                        ->native(false)
                        ->default('bottom'),

                    Select::make('banner_layout')
                        ->label(__('cookie-consent::cookie_consent.settings.banner_layout'))
                        ->options(BannerLayout::class)
                        ->native(false)
                        ->default('bar'),

                    Select::make('banner_theme')
                        ->label(__('cookie-consent::cookie_consent.settings.banner_theme'))
                        ->options(BannerTheme::class)
                        ->native(false)
                        ->default('light')
                        ->live(),

                    Grid::make(2)
                        ->schema([
                            ColorPicker::make('primary_color')
                                ->label(__('cookie-consent::cookie_consent.settings.primary_color'))
                                ->default('#10B981'),

                            ColorPicker::make('secondary_color')
                                ->label(__('cookie-consent::cookie_consent.settings.secondary_color'))
                                ->default('#6B7280'),

                            ColorPicker::make('background_color')
                                ->label(__('cookie-consent::cookie_consent.settings.background_color'))
                                ->default('#FFFFFF'),

                            ColorPicker::make('text_color')
                                ->label(__('cookie-consent::cookie_consent.settings.text_color'))
                                ->default('#1F2937'),
                        ])
                        ->visible(fn (Get $get): bool => $get('banner_theme') === BannerTheme::Custom),

                    Toggle::make('show_logo')
                        ->label(__('cookie-consent::cookie_consent.settings.show_logo'))
                        ->default(true),
                ])
                ->columns(2),

            Section::make(__('cookie-consent::cookie_consent.settings.texts'))
                ->description(__('cookie-consent::cookie_consent.settings.texts_description'))
                ->schema([
                    TextInput::make('banner_title')
                        ->label(__('cookie-consent::cookie_consent.settings.banner_title'))
                        ->default('Utilizamos cookies'),

                    Textarea::make('banner_description')
                        ->label(__('cookie-consent::cookie_consent.settings.banner_description'))
                        ->rows(3)
                        ->default('Usamos cookies propias y de terceros para mejorar tu experiencia y mostrar contenido personalizado.')
                        ->columnSpanFull(),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('accept_all_text')
                                ->label(__('cookie-consent::cookie_consent.settings.accept_all_text'))
                                ->default('Aceptar todas'),

                            TextInput::make('reject_all_text')
                                ->label(__('cookie-consent::cookie_consent.settings.reject_all_text'))
                                ->default('Rechazar todas'),

                            TextInput::make('settings_text')
                                ->label(__('cookie-consent::cookie_consent.settings.settings_text'))
                                ->default('Configurar'),

                            TextInput::make('save_settings_text')
                                ->label(__('cookie-consent::cookie_consent.settings.save_settings_text'))
                                ->default('Guardar preferencias'),
                        ]),

                    TextInput::make('privacy_policy_link_text')
                        ->label(__('cookie-consent::cookie_consent.settings.privacy_policy_link_text'))
                        ->default('Política de privacidad'),

                    TextInput::make('privacy_policy_url')
                        ->label(__('cookie-consent::cookie_consent.settings.privacy_policy_url'))
                        ->helperText(__('cookie-consent::cookie_consent.settings.privacy_policy_url_help'))
                        ->placeholder('/politica-de-privacidad')
                        // A page of this site ("/politica-de-privacidad") or a web address: the
                        // default is relative, so ->url() blocked the whole form
                        ->rules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || $value === '') {
                                    return;
                                }

                                // Not "//host" nor "/\host", which browsers also read as another site
                                $isSitePath = str_starts_with($value, '/') && ! in_array(substr($value, 1, 1), ['/', '\\'], true);
                                $isWebAddress = filter_var($value, FILTER_VALIDATE_URL) !== false
                                    && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);

                                if (! $isSitePath && ! $isWebAddress) {
                                    $fail(__('cookie-consent::cookie_consent.settings.privacy_policy_url_invalid'));
                                }
                            },
                        ]),
                ])
                ->columns(2),

            Section::make(__('cookie-consent::cookie_consent.settings.behavior'))
                ->schema([
                    Toggle::make('show_reject_all')
                        ->label(__('cookie-consent::cookie_consent.settings.show_reject_all'))
                        ->default(true),

                    Toggle::make('show_settings_button')
                        ->label(__('cookie-consent::cookie_consent.settings.show_settings_button'))
                        ->default(true),

                    Toggle::make('block_page_until_consent')
                        ->label(__('cookie-consent::cookie_consent.settings.block_page_until_consent'))
                        ->helperText(__('cookie-consent::cookie_consent.settings.block_page_until_consent_help'))
                        ->default(false),

                    TextInput::make('consent_validity_days')
                        ->label(__('cookie-consent::cookie_consent.settings.consent_validity_days'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(365)
                        ->default(365)
                        ->helperText(__('cookie-consent::cookie_consent.settings.consent_validity_days_help')),

                    Toggle::make('reconsent_on_change')
                        ->label(__('cookie-consent::cookie_consent.settings.reconsent_on_change'))
                        ->helperText(__('cookie-consent::cookie_consent.settings.reconsent_on_change_help'))
                        ->default(true),
                ])
                ->columns(2),

            Section::make(__('cookie-consent::cookie_consent.settings.integrations'))
                ->schema([
                    Toggle::make('gtm_enabled')
                        ->label(__('cookie-consent::cookie_consent.settings.gtm_enabled'))
                        ->live(),

                    TextInput::make('gtm_container_id')
                        ->label(__('cookie-consent::cookie_consent.settings.gtm_container_id'))
                        ->placeholder('GTM-XXXXXX')
                        ->visible(fn (Get $get): bool => (bool) $get('gtm_enabled')),

                    Toggle::make('consent_mode_enabled')
                        ->label(__('cookie-consent::cookie_consent.settings.consent_mode_enabled'))
                        ->helperText(__('cookie-consent::cookie_consent.settings.consent_mode_enabled_help'))
                        ->default(true),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('common.save'))
                ->submit('save'),
        ];
    }
}
