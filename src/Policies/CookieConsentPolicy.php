<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Policies;

use App\Infrastructure\Authorization\Policies\AuthorizesWithPermissions;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;

final class CookieConsentPolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(UserModel $user): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_consents.view_any');
    }

    public function view(UserModel $user, CookieConsentModel $consent): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_consents.view_any');
    }

    public function create(UserModel $user): bool
    {
        return false; // Consents are created through the API only
    }

    public function update(UserModel $user, CookieConsentModel $consent): bool
    {
        return false; // Consents are immutable
    }

    public function delete(UserModel $user, CookieConsentModel $consent): bool
    {
        return false; // Consents must be retained for GDPR compliance
    }

    public function export(UserModel $user): bool
    {
        return $this->authorize($user, 'cookie-consent:cookie_consents.export');
    }
}
