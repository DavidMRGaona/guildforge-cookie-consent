<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages;

use App\Application\Services\TimezoneServiceInterface;
use App\Filament\Concerns\HasKnowledgeGuideAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Infrastructure\Export\ConsentCsvExport;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCookieConsents extends ListRecords
{
    use HasKnowledgeGuideAction;

    protected static string $resource = CookieConsentResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->exportAction(),
            $this->knowledgeGuideAction('cookie-consent.panel.consents'),
        ]);
    }

    /**
     * Downloads what the list shows, with its search, filters and order, as a CSV file.
     */
    private function exportAction(): Action
    {
        return Action::make('export')
            ->label(__('cookie-consent::cookie_consent.consents.export.action'))
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->visible(fn (): bool => $this->canExport())
            ->action(function (): StreamedResponse {
                // A Livewire action can be called without its button: check again on the server
                abort_unless($this->canExport(), 403);

                /** @var Builder<CookieConsentModel> $consents */
                $consents = $this->getFilteredSortedTableQuery();
                $day = app(TimezoneServiceInterface::class)->today()->format('Y-m-d');
                $filename = __('cookie-consent::cookie_consent.consents.export.filename')."-{$day}.csv";

                return response()->streamDownload(function () use ($consents): void {
                    $output = fopen('php://output', 'wb');

                    if ($output === false) {
                        return;
                    }

                    app(ConsentCsvExport::class)->write($consents, $output);
                    fclose($output);
                }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
            });
    }

    private function canExport(): bool
    {
        return auth()->user()?->can('export', CookieConsentModel::class) === true;
    }
}
