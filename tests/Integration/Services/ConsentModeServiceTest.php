<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Integration\Services;

use Illuminate\Support\Str;
use Modules\CookieConsent\Application\Services\BannerConfigServiceInterface;
use Modules\CookieConsent\Application\Services\ConsentModeServiceInterface;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The browser (consent-mode.ts) and the server map a choice to Consent Mode the same way:
 * from each category's own keys. Both check the cases in tests/fixtures/consent-mode-cases.json.
 */
final class ConsentModeServiceTest extends ModuleTestCase
{
    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    /**
     * @param  array<string, bool>  $preferences
     * @param  array<string, string>  $expected
     */
    #[DataProvider('cases')]
    public function test_a_choice_maps_to_the_keys_of_each_category(array $preferences, array $expected): void
    {
        $this->useFixtureCategories();

        $this->assertSame($expected, app(ConsentModeServiceInterface::class)->mapToConsentMode($preferences));
    }

    public function test_the_banner_gets_the_same_defaults_as_the_server(): void
    {
        $defaults = self::fixture()['defaults'];

        $this->assertSame($defaults, app(ConsentModeServiceInterface::class)->defaults());
        $this->assertSame($defaults, app(BannerConfigServiceInterface::class)->getBannerConfig()->toArray()['consentModeDefaults']);
    }

    /**
     * @return array<string, array{array<string, bool>, array<string, string>}>
     */
    public static function cases(): array
    {
        $cases = [];

        foreach (self::fixture()['cases'] as $case) {
            $cases[$case['name']] = [$case['preferences'], $case['expected']];
        }

        return $cases;
    }

    private function useFixtureCategories(): void
    {
        CookieCategoryModel::query()->delete();

        foreach (self::fixture()['categories'] as $order => $category) {
            CookieCategoryModel::create([
                'id' => (string) Str::uuid(),
                'name' => $category['slug'],
                'slug' => $category['slug'],
                'description' => $category['slug'],
                'is_required' => $category['isRequired'],
                'sort_order' => $order,
                'consent_mode_keys' => $category['consentModeKeys'],
            ]);
        }
    }

    /**
     * @return array{
     *     defaults: array<string, string>,
     *     categories: list<array{slug: string, isRequired: bool, consentModeKeys: list<string>}>,
     *     cases: list<array{name: string, preferences: array<string, bool>, expected: array<string, string>}>
     * }
     */
    private static function fixture(): array
    {
        /** @var array{defaults: array<string, string>, categories: list<array{slug: string, isRequired: bool, consentModeKeys: list<string>}>, cases: list<array{name: string, preferences: array<string, bool>, expected: array<string, string>}>} */
        return json_decode((string) file_get_contents(dirname(__DIR__, 2).'/fixtures/consent-mode-cases.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
