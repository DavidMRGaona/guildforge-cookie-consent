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
        $this->seedSettings();
        SettingModel::query()->where('key', 'cookie-consent.banner_position')->update(['value' => 'top']);

        $this->seedSettings();

        $this->assertSame('top', SettingModel::query()->where('key', 'cookie-consent.banner_position')->value('value'));
    }

    public function test_it_adds_settings_that_are_missing(): void
    {
        $this->seedSettings();
        SettingModel::query()->where('key', 'cookie-consent.banner_position')->delete();

        $this->seedSettings();

        $this->assertSame('bottom', SettingModel::query()->where('key', 'cookie-consent.banner_position')->value('value'));
    }

    public function test_the_banner_texts_are_seeded_in_the_language_of_the_site(): void
    {
        app()->setLocale('en');
        SettingModel::query()->where('key', 'like', 'cookie-consent.%')->delete();

        $this->seedSettings();

        $stored = fn (string $key): ?string => SettingModel::query()->where('key', "cookie-consent.{$key}")->value('value');
        $this->assertSame('We use cookies', $stored('banner_title'));
        $this->assertSame(__('cookie-consent::cookie_consent.frontend.default_description'), $stored('banner_description'));
        $this->assertSame('Accept all', $stored('accept_all_text'));
        $this->assertSame('Reject all', $stored('reject_all_text'));
        $this->assertSame('Configure', $stored('settings_text'));
        $this->assertSame('Save preferences', $stored('save_settings_text'));
        $this->assertSame('Privacy policy', $stored('privacy_policy_link_text'));
    }

    public function test_it_does_not_seed_the_removed_logo_and_page_blocking_settings(): void
    {
        $this->seedSettings();

        $this->assertFalse(SettingModel::query()->where('key', 'cookie-consent.show_logo')->exists());
        $this->assertFalse(SettingModel::query()->where('key', 'cookie-consent.block_page_until_consent')->exists());
    }

    private function seedSettings(): void
    {
        $this->seed(CookieConsentSettingsSeeder::class);
    }
}
