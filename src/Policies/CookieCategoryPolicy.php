<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Policies;

use App\Infrastructure\Authorization\Policies\AuthorizesWithPermissions;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;

final class CookieCategoryPolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(UserModel $user): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_categories.view_any');
    }

    public function view(UserModel $user, CookieCategoryModel $category): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_categories.view_any');
    }

    public function create(UserModel $user): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_categories.create');
    }

    public function update(UserModel $user, CookieCategoryModel $category): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_categories.update');
    }

    public function delete(UserModel $user, CookieCategoryModel $category): bool
    {
        // Cannot delete required categories
        if ($category->is_required) {
            return false;
        }

        return $this->authorize($user, 'cookie-consent:cookie_categories.delete');
    }

    public function deleteAny(UserModel $user): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_categories.delete');
    }
}
