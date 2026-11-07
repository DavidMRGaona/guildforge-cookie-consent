<?php

declare(strict_types=1);

use App\Application\Services\SettingsServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «Claro» stays light even in the site's dark mode. It was the default, so sites that kept it
 * move to «Automático», which follows the site mode. A chosen «Oscuro» or «Personalizado» stays.
 */
return new class extends Migration
{
    private const string KEY = 'cookie-consent.banner_theme';

    public function up(): void
    {
        $updated = DB::table('settings')
            ->where('key', self::KEY)
            ->where('value', 'light')
            ->update(['value' => 'auto']);

        if ($updated > 0) {
            app(SettingsServiceInterface::class)->clearCache();
        }
    }

    public function down(): void
    {
        $updated = DB::table('settings')
            ->where('key', self::KEY)
            ->where('value', 'auto')
            ->update(['value' => 'light']);

        if ($updated > 0) {
            app(SettingsServiceInterface::class)->clearCache();
        }
    }
};
