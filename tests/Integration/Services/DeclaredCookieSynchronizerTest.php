<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Services;

use App\Application\Cookies\DTOs\DeclaredCookieDTO;
use App\Application\Cookies\Services\CookieDeclarationRegistryInterface;
use App\Domain\Cookies\Enums\CookieType as DeclaredCookieType;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\CookieConsent\Application\Services\ConsentServiceInterface;
use Modules\CookieConsent\Application\Services\DeclaredCookieSynchronizerInterface;
use Modules\CookieConsent\Database\Seeders\DefaultCategoriesSeeder;
use Modules\CookieConsent\Domain\Enums\CookieType;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieModel;
use Ramsey\Uuid\Uuid;
use Tests\Support\Modules\ModuleTestCase;

final class DeclaredCookieSynchronizerTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__, 3).'/database/seeders/DefaultCategoriesSeeder.php';
        $this->seed(DefaultCategoriesSeeder::class);
    }

    public function test_creates_a_missing_declared_cookie_in_its_category(): void
    {
        $this->declare('gt_guest_registrations', 'preferences', 'Remembers your registrations', '1 year');

        $created = $this->synchronizer()->sync();

        $this->assertSame(1, $created);
        $this->assertSame(1, CookieModel::query()->where('name', 'gt_guest_registrations')->count());

        $cookie = CookieModel::query()->where('name', 'gt_guest_registrations')->firstOrFail();
        $this->assertSame(
            CookieCategoryModel::query()->where('slug', 'preferences')->value('id'),
            $cookie->category_id,
        );
        $this->assertSame('GuildForge', $cookie->provider);
        $this->assertSame('Remembers your registrations', $cookie->purpose);
        $this->assertSame('1 year', $cookie->duration);
        $this->assertTrue($cookie->is_active);
    }

    public function test_a_third_party_cookie_keeps_its_type(): void
    {
        $this->app->make(CookieDeclarationRegistryInterface::class)->declare(new DeclaredCookieDTO(
            name: 'third_party_cookie',
            categorySlug: 'preferences',
            provider: 'Example provider',
            purpose: 'Purpose',
            type: DeclaredCookieType::ThirdParty,
        ));

        $this->synchronizer()->sync();

        $this->assertSame(
            CookieType::ThirdParty,
            CookieModel::query()->where('name', 'third_party_cookie')->firstOrFail()->type,
        );
    }

    public function test_creation_bumps_the_config_version_once(): void
    {
        $this->declare('gt_guest_registrations', 'preferences', 'Purpose');
        $this->declare('gt_other', 'preferences', 'Other purpose');
        $consent = $this->app->make(ConsentServiceInterface::class);
        $before = $consent->getConfigVersion();

        $this->synchronizer()->sync();
        $afterFirstSync = $consent->getConfigVersion();
        $this->synchronizer()->sync();
        $afterSecondSync = $consent->getConfigVersion();

        $this->assertSame($before + 1, $afterFirstSync);
        $this->assertSame($afterFirstSync, $afterSecondSync);
    }

    public function test_existing_cookie_with_the_same_name_is_left_untouched(): void
    {
        CookieModel::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'category_id' => CookieCategoryModel::query()->where('slug', 'analytics')->value('id'),
            'name' => 'gt_guest_registrations',
            'provider' => 'Someone',
            'purpose' => 'Edited by an admin',
            'type' => 'first_party',
            'is_active' => true,
        ]);
        $this->declare('gt_guest_registrations', 'preferences', 'Declared purpose');
        $consent = $this->app->make(ConsentServiceInterface::class);
        $before = $consent->getConfigVersion();

        $created = $this->synchronizer()->sync();

        $this->assertSame(0, $created);
        $this->assertSame(1, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
        $this->assertSame('Edited by an admin', CookieModel::query()->where('name', 'gt_guest_registrations')->value('purpose'));
        $this->assertSame($before, $consent->getConfigVersion());
    }

    public function test_unknown_category_is_skipped_with_a_warning(): void
    {
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        $this->declare('gt_guest_registrations', 'missing', 'Purpose');

        $created = $this->synchronizer()->sync();

        $this->assertSame(0, $created);
        $this->assertSame(0, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
    }

    public function test_migrate_runs_the_sync(): void
    {
        $this->declare('gt_guest_registrations', 'preferences', 'Purpose');

        Event::dispatch(new NoPendingMigrations('up'));

        $this->assertSame(1, CookieModel::query()->where('name', 'gt_guest_registrations')->count());

        CookieModel::query()->where('name', 'gt_guest_registrations')->delete();

        Event::dispatch(new MigrationsEnded('up'));

        $this->assertSame(1, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
    }

    public function test_a_failing_sync_never_breaks_the_migration_events(): void
    {
        $this->app->bind(DeclaredCookieSynchronizerInterface::class, fn () => new class implements DeclaredCookieSynchronizerInterface
        {
            public function sync(): int
            {
                throw new \RuntimeException('boom');
            }
        });
        Log::shouldReceive('error')->once();

        Event::dispatch(new MigrationsEnded('up'));

        $this->addToAssertionCount(1);
    }

    public function test_a_throwing_declaration_callback_never_breaks_the_migration_events(): void
    {
        $this->app->forgetInstance(CookieDeclarationRegistryInterface::class);
        $this->app->afterResolving(CookieDeclarationRegistryInterface::class, function (): void {
            throw new \TypeError('declaration built against another signature');
        });
        Log::shouldReceive('error')->once();

        Event::dispatch(new MigrationsEnded('up'));
        Event::dispatch(new NoPendingMigrations('up'));

        $this->addToAssertionCount(1);
    }

    public function test_rollbacks_do_not_run_the_sync(): void
    {
        $this->declare('gt_guest_registrations', 'preferences', 'Purpose');

        Event::dispatch(new MigrationsEnded('down'));
        Event::dispatch(new NoPendingMigrations('down'));

        $this->assertSame(0, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
    }

    public function test_pretend_runs_do_not_run_the_sync(): void
    {
        $this->declare('gt_guest_registrations', 'preferences', 'Purpose');

        Event::dispatch(new MigrationsEnded('up', ['pretend' => true]));

        $this->assertSame(0, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
    }

    public function test_the_sync_is_scheduled_hourly(): void
    {
        $events = collect($this->app->make(Schedule::class)->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'cookie-consent:sync-declared-cookies'));

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
    }

    private function declare(string $name, string $slug, string $purpose, ?string $duration = null): void
    {
        $this->app->make(CookieDeclarationRegistryInterface::class)->declare(
            new DeclaredCookieDTO($name, $slug, 'GuildForge', $purpose, $duration)
        );
    }

    private function synchronizer(): DeclaredCookieSynchronizerInterface
    {
        return $this->app->make(DeclaredCookieSynchronizerInterface::class);
    }
}
