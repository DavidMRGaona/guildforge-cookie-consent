<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Tests\Feature\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\PermissionModel;
use App\Infrastructure\Persistence\Eloquent\Models\RoleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource;
use Modules\CookieConsent\Filament\Resources\CookieCategoryResource\Pages\ListCookieCategories;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Tests\Support\Modules\InteractsWithModulePanel;
use Tests\Support\Modules\ModuleTestCase;

final class CookieCategoryPolicyTest extends ModuleTestCase
{
    use InteractsWithModulePanel;

    private const string VIEW_ANY = 'cookie-consent:cookie_categories.view_any';

    private const string UPDATE = 'cookie-consent:cookie_categories.update';

    protected ?string $moduleName = 'cookie-consent';

    protected bool $autoEnableModule = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerOnAdminPanel([CookieCategoryResource::class]);
    }

    public function test_reordering_follows_the_update_permission(): void
    {
        $this->assertTrue($this->userWith([self::VIEW_ANY, self::UPDATE])->can('reorder', CookieCategoryModel::class));
        $this->assertFalse($this->userWith([self::VIEW_ANY])->can('reorder', CookieCategoryModel::class));
        $this->assertFalse($this->userWith([])->can('reorder', CookieCategoryModel::class));
    }

    public function test_a_holder_of_the_update_permission_reorders_the_categories_from_the_list(): void
    {
        [$first, $second] = $this->twoCategories();
        $this->actingAs($this->userWith([self::VIEW_ANY, self::UPDATE]));

        Livewire::test(ListCookieCategories::class)
            ->call('reorderTable', [$second->getKey(), $first->getKey()]);

        $this->assertLessThan($first->refresh()->sort_order, $second->refresh()->sort_order);
    }

    public function test_without_the_update_permission_the_order_does_not_change(): void
    {
        [$first, $second] = $this->twoCategories();
        $this->actingAs($this->userWith([self::VIEW_ANY]));

        Livewire::test(ListCookieCategories::class)
            ->call('reorderTable', [$second->getKey(), $first->getKey()]);

        $this->assertSame(1, $first->refresh()->sort_order);
        $this->assertSame(2, $second->refresh()->sort_order);
    }

    /**
     * @return array{CookieCategoryModel, CookieCategoryModel}
     */
    private function twoCategories(): array
    {
        CookieCategoryModel::query()->delete();

        return [$this->newCategory('first', 1), $this->newCategory('second', 2)];
    }

    private function newCategory(string $slug, int $sortOrder): CookieCategoryModel
    {
        return CookieCategoryModel::create([
            'id' => (string) Str::uuid(),
            'name' => "Category {$slug}",
            'slug' => "test-{$slug}",
            'description' => 'Test category',
            'is_required' => false,
            'is_system' => false,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function userWith(array $permissionKeys): UserModel
    {
        $role = RoleModel::query()->create(['name' => 'tester_'.uniqid(), 'display_name' => 'Permissions tester']);

        foreach ($permissionKeys as $key) {
            $permission = PermissionModel::query()->firstOrCreate(
                ['key' => $key],
                ['label' => $key, 'resource' => 'cookie-consent', 'action' => Str::afterLast($key, '.'), 'module' => 'cookie-consent'],
            );
            $role->permissions()->attach($permission->id);
        }

        $user = UserModel::factory()->editor()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
