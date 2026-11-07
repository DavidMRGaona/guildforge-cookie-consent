<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Infrastructure\Export;

use App\Application\Services\TimezoneServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieCategoryModel;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;

/**
 * The consent register as a CSV for spreadsheets: UTF-8 with BOM, so accents read right, and `;`
 * as separator, which Excel expects with Spanish regional settings.
 *
 * It carries what proves a consent (visitor, signed-in user's name, method, configuration version,
 * the choice for each category and the dates) and nothing more: no IP hash, no user agent, no email.
 */
final readonly class ConsentCsvExport
{
    private const int CHUNK_SIZE = 500;

    public function __construct(private TimezoneServiceInterface $timezone) {}

    /**
     * @param  Builder<CookieConsentModel>  $consents  The consents to write, in the order they should appear
     * @param  resource  $output
     */
    public function write(Builder $consents, $output): void
    {
        $categories = CookieCategoryModel::query()->orderBy('sort_order')->get(['slug', 'name']);

        fwrite($output, "\u{FEFF}");
        $this->writeRow($output, [
            __('cookie-consent::cookie_consent.consents.fields.visitor_id'),
            __('cookie-consent::cookie_consent.consents.fields.user'),
            __('cookie-consent::cookie_consent.consents.fields.consent_method'),
            __('cookie-consent::cookie_consent.consents.fields.config_version'),
            ...$categories->map(static fn (CookieCategoryModel $category): string => $category->name)->all(),
            __('cookie-consent::cookie_consent.consents.fields.consented_at'),
            __('cookie-consent::cookie_consent.consents.fields.expires_at'),
        ]);

        $consents
            ->with(['user' => static fn ($query) => $query->withTrashed()])
            ->orderBy('id')
            ->lazy(self::CHUNK_SIZE)
            ->each(function (CookieConsentModel $consent) use ($categories, $output): void {
                $this->writeRow($output, [
                    $consent->visitor_id,
                    $consent->user->name ?? '',
                    $consent->consent_method->getLabel(),
                    $consent->config_version,
                    ...$categories->map(fn (CookieCategoryModel $category): string => $this->choice($consent, $category->slug))->all(),
                    $this->timezone->format($consent->consented_at),
                    $this->timezone->format($consent->expires_at),
                ]);
            });
    }

    /**
     * Yes or no for a category the visitor answered; blank when the consent predates it.
     */
    private function choice(CookieConsentModel $consent, string $slug): string
    {
        if (! array_key_exists($slug, $consent->preferences)) {
            return '';
        }

        return $consent->preferences[$slug]
            ? __('cookie-consent::cookie_consent.consents.export.yes')
            : __('cookie-consent::cookie_consent.consents.export.no');
    }

    /**
     * @param  resource  $output
     * @param  list<string|int>  $values
     */
    private function writeRow($output, array $values): void
    {
        fputcsv($output, array_map($this->neutraliseFormula(...), $values), separator: ';', escape: '');
    }

    /**
     * Prefix what a spreadsheet would run as a formula (CSV injection) with a single quote.
     */
    private function neutraliseFormula(string|int $value): string|int
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
