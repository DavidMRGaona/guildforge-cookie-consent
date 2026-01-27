<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\CookieConsent\Domain\Enums\ConsentMethod;
use Modules\CookieConsent\Domain\Repositories\CookieConsentRepositoryInterface;

class ConsentStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        $repository = app(CookieConsentRepositoryInterface::class);

        $totalConsents = $repository->count();
        $byMethod = $repository->countByMethod();

        $stats = [
            Stat::make(__('cookie-consent::cookie_consent.widgets.stats.total_consents'), number_format($totalConsents))
                ->icon('heroicon-o-shield-check')
                ->color('success'),
        ];

        foreach ($byMethod as $method => $count) {
            $methodEnum = ConsentMethod::tryFrom($method);
            if ($methodEnum !== null) {
                $stats[] = Stat::make($methodEnum->getLabel(), number_format($count))
                    ->color('gray');
            }
        }

        return $stats;
    }
}
