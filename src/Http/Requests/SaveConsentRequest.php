<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Consent can be submitted by anyone
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'visitor_id' => ['required', 'uuid'],
            'preferences' => ['required', 'array'],
            'preferences.*' => ['required', 'boolean'],
            'config_version' => ['required', 'integer', 'min:1'],
            'consent_method' => ['sometimes', 'string', 'in:banner,settings_page,api'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'visitor_id.required' => __('cookie-consent::cookie_consent.validation.visitor_id_required'),
            'visitor_id.uuid' => __('cookie-consent::cookie_consent.validation.visitor_id_uuid'),
            'preferences.required' => __('cookie-consent::cookie_consent.validation.preferences_required'),
            'preferences.array' => __('cookie-consent::cookie_consent.validation.preferences_array'),
            'config_version.required' => __('cookie-consent::cookie_consent.validation.config_version_required'),
        ];
    }
}
