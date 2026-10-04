<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Database;

use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Modules\CookieConsent\Database\Seeders\CookieConsentSettingsSeeder;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The seeder only adds missing settings: running it again must not reset what an
 * admin configured.
 */
final class CookieConsentSettingsSeederTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        // database/ is outside the module's autoloaded src/
        require_once dirname(__DIR__, 3).'/database/seeders/CookieConsentSettingsSeeder.php';
    }

    public function test_running_the_seeder_again_keeps_configured_settings(): void
    {
        $this->seed(CookieConsentSettingsSeeder::class);
        SettingModel::query()->where('key', 'cookie-consent.banner_position')->update(['value' => 'top']);

        $this->seed(CookieConsentSettingsSeeder::class);

        $this->assertSame('top', SettingModel::query()->where('key', 'cookie-consent.banner_position')->value('value'));
    }

    public function test_it_adds_settings_that_are_missing(): void
    {
        $this->seed(CookieConsentSettingsSeeder::class);
        SettingModel::query()->where('key', 'cookie-consent.banner_position')->delete();

        $this->seed(CookieConsentSettingsSeeder::class);

        $this->assertSame('bottom', SettingModel::query()->where('key', 'cookie-consent.banner_position')->value('value'));
    }
}
