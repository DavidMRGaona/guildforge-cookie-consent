<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Database;

use App\Application\Services\SettingsServiceInterface;
use App\Infrastructure\Persistence\Eloquent\Models\SettingModel;
use Illuminate\Database\Migrations\Migration;
use Tests\Support\Modules\ModuleTestCase;

/**
 * «Claro» stayed light in the site's dark mode: sites that kept it move to the automatic
 * theme, which follows the site mode. A deliberate «Oscuro» or «Personalizado» is kept.
 */
final class AutoBannerThemeMigrationTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    public function test_it_moves_the_light_theme_to_the_automatic_one(): void
    {
        $this->storeTheme('light');

        $this->runMigration();

        $this->assertSame('auto', $this->app->make(SettingsServiceInterface::class)->get('cookie-consent.banner_theme'));
    }

    public function test_it_keeps_the_dark_and_custom_themes(): void
    {
        foreach (['dark', 'custom'] as $theme) {
            $this->storeTheme($theme);

            $this->runMigration();

            $this->assertSame($theme, SettingModel::query()->where('key', 'cookie-consent.banner_theme')->value('value'));
        }
    }

    public function test_it_does_nothing_without_a_stored_theme(): void
    {
        SettingModel::query()->where('key', 'cookie-consent.banner_theme')->delete();

        $this->runMigration();

        $this->assertFalse(SettingModel::query()->where('key', 'cookie-consent.banner_theme')->exists());
    }

    private function storeTheme(string $theme): void
    {
        SettingModel::query()->updateOrCreate(['key' => 'cookie-consent.banner_theme'], ['value' => $theme]);
        // Read once so a stale cached value would show up after the migration
        $this->app->make(SettingsServiceInterface::class)->get('cookie-consent.banner_theme');
    }

    private function runMigration(): void
    {
        $migration = require dirname(__DIR__, 3).'/database/migrations/2026_11_07_000001_move_light_banner_theme_to_auto.php';
        if (! $migration instanceof Migration || ! method_exists($migration, 'up')) {
            $this->fail('The migration has no up()');
        }

        $migration->up();
    }
}
