<?php

declare(strict_types=1);

namespace Modules\CookieConsent;

use App\Application\Modules\DTOs\NavigationItemDTO;
use App\Application\Modules\DTOs\PermissionDTO;
use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Modules\ModuleServiceProvider;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Livewire\Livewire;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Application\Services\ScriptInjectorServiceInterface;
use Modules\CookieConsent\Domain\Enums\BannerLayout;
use Modules\CookieConsent\Domain\Enums\BannerPosition;
use Modules\CookieConsent\Domain\Enums\BannerTheme;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieConsentRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieScriptRepositoryInterface;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\CookiesRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\ScriptsRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Http\Middleware\InjectCookieScripts;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories\EloquentCookieCategoryRepository;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories\EloquentCookieConsentRepository;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories\EloquentCookieRepository;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Repositories\EloquentCookieScriptRepository;
use Modules\CookieConsent\Infrastructure\Services\BannerConfigService;
use Modules\CookieConsent\Infrastructure\Services\ConsentModeService;
use Modules\CookieConsent\Infrastructure\Services\ConsentService;
use Modules\CookieConsent\Infrastructure\Services\ScriptInjectorService;
use Modules\CookieConsent\Listeners\IncrementConfigVersionOnChange;
use Modules\CookieConsent\Policies\CookieCategoryPolicy;
use Modules\CookieConsent\Policies\CookieConsentPolicy;

final class CookieConsentServiceProvider extends ModuleServiceProvider
{
    public function moduleName(): string
    {
        return 'cookie-consent';
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            $this->modulePath('config/module.php'),
            'cookie-consent'
        );

        // Bind repository interfaces to implementations
        $this->app->bind(CookieCategoryRepositoryInterface::class, EloquentCookieCategoryRepository::class);
        $this->app->bind(CookieRepositoryInterface::class, EloquentCookieRepository::class);
        $this->app->bind(CookieScriptRepositoryInterface::class, EloquentCookieScriptRepository::class);
        $this->app->bind(CookieConsentRepositoryInterface::class, EloquentCookieConsentRepository::class);

        // Bind service interfaces to implementations
        $this->app->bind(ConsentServiceInterface::class, ConsentService::class);
        $this->app->bind(BannerConfigServiceInterface::class, BannerConfigService::class);
        $this->app->bind(ConsentModeServiceInterface::class, ConsentModeService::class);
        $this->app->bind(ScriptInjectorServiceInterface::class, ScriptInjectorService::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Register middleware globally for script injection
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', InjectCookieScripts::class);

        // Register event listeners
        Event::listen(
            CookieConfigurationChanged::class,
            IncrementConfigVersionOnChange::class
        );

        // Share cookie consent data with Inertia
        if (class_exists(Inertia::class)) {
            Inertia::share('cookieConsent', function () {
                if (! $this->app->bound(ConsentServiceInterface::class)) {
                    return null;
                }

                try {
                    $consentService = $this->app->make(ConsentServiceInterface::class);
                    $bannerConfigService = $this->app->make(BannerConfigServiceInterface::class);

                    return [
                        'categories' => array_map(
                            fn ($dto) => $dto->toArray(),
                            $consentService->getCategories()
                        ),
                        'config' => $bannerConfigService->getBannerConfig()->toArray(),
                    ];
                } catch (\Throwable) {
                    return null;
                }
            });
        }

        $this->registerLivewireComponents();
    }

    public function onEnable(): void
    {
        // Seeders are automatically run by ModuleSeederRunner
        // when the module is enabled for the first time
    }

    public function onDisable(): void
    {
        // Cleanup if needed
    }

    /**
     * @return array<class-string<\Filament\Resources\Resource>>
     */
    public function registerFilamentResources(): array
    {
        return [
            CookieCategoryResource::class,
            CookieConsentResource::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public function registerPolicies(): array
    {
        return [
            CookieCategoryModel::class => CookieCategoryPolicy::class,
            CookieConsentModel::class => CookieConsentPolicy::class,
        ];
    }

    /**
     * @return array<string, array{icon?: string, sort?: int}>
     */
    public function registerNavigationGroups(): array
    {
        return [
            __('cookie-consent::cookie_consent.navigation_group') => [
                'sort' => 50,
            ],
        ];
    }

    /**
     * @return array<PermissionDTO>
     */
    public function registerPermissions(): array
    {
        return [
            new PermissionDTO(
                name: 'cookie_categories.view_any',
                label: __('cookie-consent::cookie_consent.permissions.categories_view_any'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: ['editor'],
            ),
            new PermissionDTO(
                name: 'cookie_categories.create',
                label: __('cookie-consent::cookie_consent.permissions.categories_create'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: [],
            ),
            new PermissionDTO(
                name: 'cookie_categories.update',
                label: __('cookie-consent::cookie_consent.permissions.categories_update'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: [],
            ),
            new PermissionDTO(
                name: 'cookie_categories.delete',
                label: __('cookie-consent::cookie_consent.permissions.categories_delete'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: [],
            ),
            new PermissionDTO(
                name: 'cookie_consents.view_any',
                label: __('cookie-consent::cookie_consent.permissions.consents_view_any'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: [],
            ),
            new PermissionDTO(
                name: 'cookie_consents.export',
                label: __('cookie-consent::cookie_consent.permissions.consents_export'),
                group: __('cookie-consent::cookie_consent.navigation'),
                module: 'cookie-consent',
                roles: [],
            ),
        ];
    }

    /**
     * @return array<NavigationItemDTO>
     */
    public function registerNavigation(): array
    {
        return [
            new NavigationItemDTO(
                label: __('cookie-consent::cookie_consent.navigation'),
                route: 'filament.admin.resources.cookie-categories.index',
                icon: 'heroicon-o-squares-2x2',
                group: __('cookie-consent::cookie_consent.navigation_group'),
                sort: 1,
                permissions: ['cookie-consent:cookie_categories.view_any'],
                module: 'cookie-consent',
            ),
        ];
    }

    /**
     * @return array<SlotRegistrationDTO>
     */
    public function registerSlots(): array
    {
        return [
            new SlotRegistrationDTO(
                slot: 'after-footer',
                component: 'components/CookieBanner.vue',
                module: $this->moduleName(),
                order: 100,
                props: [],
                dataKeys: ['cookieConsent'],
            ),
        ];
    }

    /**
     * @return array<\Filament\Forms\Components\Component>
     */
    public function getSettingsSchema(): array
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
                        ->visible(fn (Get $get): bool => $get('banner_theme') === 'custom'),

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
                        ->url()
                        ->placeholder('/politica-de-privacidad'),
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

    private function registerLivewireComponents(): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        Livewire::component(
            'modules.cookie-consent.filament.relation-managers.cookies-relation-manager',
            CookiesRelationManager::class
        );
        Livewire::component(
            'modules.cookie-consent.filament.relation-managers.scripts-relation-manager',
            ScriptsRelationManager::class
        );
    }
}
