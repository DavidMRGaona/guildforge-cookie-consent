<?php

declare(strict_types=1);

namespace Modules\CookieConsent;

use Throwable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use App\Application\Modules\DTOs\NavigationItemDTO;
use App\Application\Modules\DTOs\PermissionDTO;
use App\Application\Modules\DTOs\SlotRegistrationDTO;
use App\Modules\ModuleServiceProvider;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Livewire\Livewire;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Application\Services\CookieDeclarationRegistryInterface;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;
use Modules\CookieConsent\Console\Commands\SyncDeclaredCookiesCommand;
use Modules\CookieConsent\Application\Services\ScriptInjectorServiceInterface;
use Modules\CookieConsent\Domain\Events\CookieConfigurationChanged;
use Modules\CookieConsent\Domain\Repositories\CookieCategoryRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieConsentRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieRepositoryInterface;
use Modules\CookieConsent\Domain\Repositories\CookieScriptRepositoryInterface;
use Modules\CookieConsent\Filament\Pages\CookieConsentSettings;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\CookiesRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\ScriptsRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Widgets\ConsentStatsWidget;
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
use Modules\CookieConsent\Infrastructure\Services\CookieDeclarationRegistry;
use Modules\CookieConsent\Infrastructure\Services\DeclaredCookieSynchronizer;
use Modules\CookieConsent\Infrastructure\Services\ScriptInjectorService;
use Modules\CookieConsent\Listeners\IncrementConfigVersionOnChange;
use Modules\CookieConsent\Listeners\SyncDeclaredCookiesAfterMigrations;
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

        $this->forceLoadConfig($this->modulePath('config/module.php'), 'cookie-consent');

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

        // Other modules declare their cookies here during boot: it must outlive each resolution
        $this->app->singleton(CookieDeclarationRegistryInterface::class, CookieDeclarationRegistry::class);
        $this->app->bind(DeclaredCookieSynchronizerInterface::class, DeclaredCookieSynchronizer::class);
    }

    /**
     * Enabling a module or installing this one does not run the sync in a booted process: catch up hourly.
     */
    private function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('cookie-consent:sync-declared-cookies')
                ->hourly()
                ->withoutOverlapping(10);
        });
    }

    public function boot(): void
    {
        parent::boot();

        // The banner writes the consent cookie in the browser: encrypted cookies that
        // fail to decrypt reach the request as null, so the middleware would never
        // see the visitor's choices
        EncryptCookies::except([InjectCookieScripts::CONSENT_COOKIE_NAME]);

        // Register middleware globally for script injection
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', InjectCookieScripts::class);

        // Register event listeners
        Event::listen(
            CookieConfigurationChanged::class,
            IncrementConfigVersionOnChange::class
        );

        // Modules migrate through Artisan::call('migrate'): create the cookies they declare afterwards
        Event::listen(MigrationsEnded::class, SyncDeclaredCookiesAfterMigrations::class);
        Event::listen(NoPendingMigrations::class, SyncDeclaredCookiesAfterMigrations::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SyncDeclaredCookiesCommand::class]);
        }

        $this->registerSchedule();

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
                } catch (Throwable) {
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
     * @return array<class-string<Resource>>
     */
    public function registerFilamentResources(): array
    {
        return [
            CookieCategoryResource::class,
            CookieConsentResource::class,
        ];
    }

    /**
     * @return array<class-string<Page>>
     */
    public function registerFilamentPages(): array
    {
        return [
            CookieConsentSettings::class,
        ];
    }

    /**
     * Dashboard widgets, discovered by the admin panel.
     *
     * @return array<class-string<Widget>>
     */
    public function registerFilamentWidgets(): array
    {
        return [
            ConsentStatsWidget::class,
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
     * The banner settings have their own page (CookieConsentSettings), which stores
     * them where the banner reads them: the generic module settings page would
     * write them to config/settings.php instead.
     *
     * @return array<\Filament\Schemas\Components\Component>
     */
    public function getSettingsSchema(): array
    {
        return [];
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
