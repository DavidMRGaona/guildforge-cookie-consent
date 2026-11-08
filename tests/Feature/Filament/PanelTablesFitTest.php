<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Filament;

use App\Filament\Actions\RowDetailsAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Livewire;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\EditCookieCategory;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\ListCookieCategories;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\CookiesRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\RelationManagers\ScriptsRelationManager;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

/**
 * The panel tables overflowed their container (417 to 469 px at 375 px) and pushed the row
 * actions out of sight: the row actions sit in one menu and the secondary columns appear only
 * from the width where they fit. What the consent records show, and to whom, does not change.
 */
final class PanelTablesFitTest extends ModuleTestCase
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

    /**
     * @return array<string, array{class-string<HasTable>}>
     */
    public static function tablesWithTwoRowActions(): array
    {
        return [
            'categories' => [ListCookieCategories::class],
            'cookies' => [CookiesRelationManager::class],
            'scripts' => [ScriptsRelationManager::class],
        ];
    }

    /**
     * @param  class-string<HasTable>  $component
     */
    #[DataProvider('tablesWithTwoRowActions')]
    public function test_edit_and_delete_sit_in_one_menu(string $component): void
    {
        $actions = $this->table($component)->getRecordActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ActionGroup::class, $actions[0]);
    }

    public function test_the_consents_keep_their_view_action_next_to_the_details(): void
    {
        $actions = $this->table(ListCookieConsents::class)->getRecordActions();

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ActionGroup::class, $actions[0]);
        $this->assertSame([RowDetailsAction::NAME, 'view'], array_keys($actions[0]->getFlatActions()));
        $this->assertInstanceOf(ViewAction::class, $actions[0]->getFlatActions()['view']);
    }

    /**
     * @param  class-string<HasTable>  $component
     */
    #[DataProvider('tablesWithTwoRowActions')]
    public function test_row_details_come_first_in_the_menu(string $component): void
    {
        $table = $this->table($component);
        $group = $table->getRecordActions()[0];

        $this->assertInstanceOf(ActionGroup::class, $group);
        $this->assertSame(RowDetailsAction::NAME, array_key_first($group->getFlatActions()));
        $this->assertNotNull($table->getAction(RowDetailsAction::NAME));
    }

    public function test_the_counters_are_centred(): void
    {
        $categories = $this->table(ListCookieCategories::class);
        foreach (['sort_order', 'cookies_count', 'scripts_count'] as $name) {
            $this->assertSame(Alignment::Center, $categories->getColumn($name)?->getAlignment(), $name);
        }
        $this->assertSame(Alignment::Center, $this->table(ScriptsRelationManager::class)->getColumn('sort_order')?->getAlignment());
        $this->assertSame(Alignment::Center, $this->table(ListCookieConsents::class)->getColumn('config_version')?->getAlignment());
    }

    /**
     * @return array<string, array{class-string<HasTable>, string, ?string}>
     */
    public static function columnBreakpoints(): array
    {
        return [
            'categories: name always shown' => [ListCookieCategories::class, 'name', null],
            'categories: required always shown' => [ListCookieCategories::class, 'is_required', null],
            'categories: cookies count' => [ListCookieCategories::class, 'cookies_count', 'sm'],
            'categories: scripts count' => [ListCookieCategories::class, 'scripts_count', 'md'],
            'categories: order' => [ListCookieCategories::class, 'sort_order', 'md'],
            'categories: slug' => [ListCookieCategories::class, 'slug', 'xl'],
            'consents: visitor always shown' => [ListCookieConsents::class, 'visitor_id', null],
            'consents: method' => [ListCookieConsents::class, 'consent_method', 'sm'],
            'consents: date always shown' => [ListCookieConsents::class, 'consented_at', null],
            'consents: user' => [ListCookieConsents::class, 'user.name', 'sm'],
            'consents: preferences' => [ListCookieConsents::class, 'preferences', 'md'],
            'consents: config version' => [ListCookieConsents::class, 'config_version', 'xl'],
            'cookies: name always shown' => [CookiesRelationManager::class, 'name', null],
            'cookies: type' => [CookiesRelationManager::class, 'type', 'sm'],
            'cookies: active always shown' => [CookiesRelationManager::class, 'is_active', null],
            'cookies: provider' => [CookiesRelationManager::class, 'provider', 'md'],
            'cookies: domain' => [CookiesRelationManager::class, 'domain', 'xl'],
            'cookies: duration' => [CookiesRelationManager::class, 'duration', 'xl'],
            'scripts: name always shown' => [ScriptsRelationManager::class, 'name', null],
            'scripts: active always shown' => [ScriptsRelationManager::class, 'is_active', null],
            'scripts: order' => [ScriptsRelationManager::class, 'sort_order', 'sm'],
            'scripts: description' => [ScriptsRelationManager::class, 'description', 'md'],
        ];
    }

    /**
     * @param  class-string<HasTable>  $component
     */
    #[DataProvider('columnBreakpoints')]
    public function test_secondary_columns_appear_from_the_width_where_they_fit(string $component, string $column, ?string $breakpoint): void
    {
        $this->assertSame($breakpoint, $this->table($component)->getColumn($column)?->getVisibleFrom());
    }

    public function test_the_consents_keep_the_columns_they_showed(): void
    {
        $columns = array_keys($this->table(ListCookieConsents::class)->getColumns());

        $this->assertSame(
            ['visitor_id', 'user.name', 'consent_method', 'config_version', 'preferences', 'consented_at', 'expires_at', 'ip_hash'],
            $columns,
        );
    }

    /**
     * The category name and the header of the consent date are the widest cells that can break
     * onto several lines, so they do it rather than widen the table on a phone.
     */
    public function test_the_widest_cells_break_onto_several_lines(): void
    {
        $name = $this->table(ListCookieCategories::class)->getColumn('name');
        $date = $this->table(ListCookieConsents::class)->getColumn('consented_at');

        $this->assertInstanceOf(TextColumn::class, $name);
        $this->assertTrue($name->canWrap());
        $this->assertInstanceOf(TextColumn::class, $date);
        $this->assertTrue($date->canHeaderWrap());
        $this->assertTrue($date->canWrap());

        foreach (['visitor_id', 'user.name', 'consent_method'] as $header) {
            $this->assertTrue($this->table(ListCookieConsents::class)->getColumn($header)?->canHeaderWrap(), $header);
        }

        $user = $this->table(ListCookieConsents::class)->getColumn('user.name');
        $this->assertInstanceOf(TextColumn::class, $user);
        $this->assertTrue($user->canWrap());
    }

    /**
     * @param  class-string<HasTable>  $component
     */
    private function table(string $component): Table
    {
        $parameters = is_a($component, ListCookieCategories::class, true) || is_a($component, ListCookieConsents::class, true)
            ? []
            : ['ownerRecord' => CookieCategoryModel::query()->orderBy('sort_order')->firstOrFail(), 'pageClass' => EditCookieCategory::class];

        $instance = Livewire::test($component, $parameters)->instance();
        $this->assertInstanceOf(HasTable::class, $instance);

        return $instance->getTable();
    }
}
