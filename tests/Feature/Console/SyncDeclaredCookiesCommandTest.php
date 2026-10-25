<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Console;

use App\Application\Cookies\DTOs\DeclaredCookieDTO;
use App\Application\Cookies\Services\CookieDeclarationRegistryInterface;
use Illuminate\Contracts\Console\Kernel;
use Modules\CookieConsent\Console\Commands\SyncDeclaredCookiesCommand;
use Modules\CookieConsent\Database\Seeders\DefaultCategoriesSeeder;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieModel;
use Tests\Support\Modules\ModuleTestCase;

final class SyncDeclaredCookiesCommandTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        // The module is booted after the console application exists, so its commands have to be
        // added to it by hand
        $this->app->make(Kernel::class)->registerCommand($this->app->make(SyncDeclaredCookiesCommand::class));
    }

    public function test_the_command_creates_declared_cookies_and_prints_the_count(): void
    {
        require_once dirname(__DIR__, 3).'/database/seeders/DefaultCategoriesSeeder.php';
        $this->seed(DefaultCategoriesSeeder::class);
        $this->app->make(CookieDeclarationRegistryInterface::class)->declare(
            new DeclaredCookieDTO('gt_guest_registrations', 'preferences', 'GuildForge', 'Purpose')
        );

        $this->artisan('cookie-consent:sync-declared-cookies')
            ->expectsOutputToContain('1')
            ->assertExitCode(0);

        $this->assertSame(1, CookieModel::query()->where('name', 'gt_guest_registrations')->count());
    }
}
