<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\EditCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ViewCookieConsent;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class PanelTranslationsTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([CookieCategoryResource::class, CookieConsentResource::class]);
        $this->actingAsPanelAdmin();
    }

    public function test_every_language_has_the_same_keys(): void
    {
        $spanish = array_keys(Arr::dot($this->translations('es')));
        $english = array_keys(Arr::dot($this->translations('en')));

        $this->assertSame([], array_values(array_diff($spanish, $english)), 'Missing in English');
        $this->assertSame([], array_values(array_diff($english, $spanish)), 'Missing in Spanish');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function panelTexts(): iterable
    {
        yield 'category help for required' => ['es', 'Las categorías requeridas no pueden ser rechazadas por el usuario'];
        yield 'category help for required, English' => ['en', 'Required categories cannot be declined by the visitor'];
        yield 'consent mode section' => ['es', 'Selecciona las claves de Google Consent Mode v2 asociadas a esta categoría'];
        yield 'consent mode section, English' => ['en', 'Pick the Google Consent Mode v2 keys tied to this category'];
    }

    #[DataProvider('panelTexts')]
    public function test_the_category_form_is_translated(string $locale, string $text): void
    {
        app()->setLocale($locale);
        $category = CookieCategoryModel::create([
            'id' => (string) Str::uuid(),
            'name' => 'Custom',
            'slug' => 'custom',
            'description' => 'Custom category',
            'is_required' => false,
            'is_system' => false,
            'sort_order' => 100,
        ]);

        Livewire::test(EditCookieCategory::class, ['record' => $category->getRouteKey()])->assertSee($text);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function consentTexts(): iterable
    {
        yield 'Spanish' => ['es', ['Información del visitante', 'Consentimiento', 'Anónimo']];
        yield 'English' => ['en', ['Visitor information', 'Consent', 'Anonymous']];
    }

    /**
     * @param  list<string>  $texts
     */
    #[DataProvider('consentTexts')]
    public function test_the_consent_page_is_translated(string $locale, array $texts): void
    {
        app()->setLocale($locale);
        $consent = CookieConsentModel::create([
            'id' => (string) Str::uuid(),
            'visitor_id' => '6f1c2a3b-4d5e-4f60-8a71-92b3c4d5e6f7',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'PHPUnit',
            'preferences' => ['analytics' => true],
            'config_version' => 1,
            'consent_method' => 'banner',
            'consented_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        Livewire::test(ViewCookieConsent::class, ['record' => $consent->getRouteKey()])->assertSee($texts);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function placeholderKeys(): iterable
    {
        foreach (['name', 'provider', 'domain', 'duration'] as $field) {
            yield "cookie {$field}" => ["cookies.placeholders.{$field}"];
        }

        foreach (['name', 'description'] as $field) {
            yield "script {$field}" => ["scripts.placeholders.{$field}"];
        }
    }

    #[DataProvider('placeholderKeys')]
    public function test_the_relation_manager_placeholders_are_translated(string $key): void
    {
        foreach (['es', 'en'] as $locale) {
            $this->assertNotSame("cookie-consent::cookie_consent.{$key}", __("cookie-consent::cookie_consent.{$key}", [], $locale), "{$key} in {$locale}");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function translations(string $locale): array
    {
        /** @var array<string, mixed> $translations */
        $translations = require dirname(__DIR__, 3)."/lang/{$locale}/cookie_consent.php";

        return $translations;
    }
}
