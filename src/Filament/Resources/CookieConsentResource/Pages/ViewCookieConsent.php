<?php

declare(strict_types=1);

namespace Modules\CookieConsent\Filament\Resources\CookieConsentResource\Pages;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Modules\CookieConsent\Filament\Resources\CookieConsentResource;
use Modules\CookieConsent\Infrastructure\Persistence\Eloquent\Models\CookieConsentModel;

class ViewCookieConsent extends ViewRecord
{
    protected static string $resource = CookieConsentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información del visitante')
                    ->schema([
                        TextEntry::make('visitor_id')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.visitor_id'))
                            ->copyable(),

                        TextEntry::make('user.name')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.user'))
                            ->placeholder('Anónimo'),

                        TextEntry::make('ip_hash')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.ip_hash'))
                            ->copyable(),

                        TextEntry::make('user_agent')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.user_agent'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Consentimiento')
                    ->schema([
                        TextEntry::make('consent_method')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.consent_method'))
                            ->badge(),

                        TextEntry::make('config_version')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.config_version')),

                        TextEntry::make('consented_at')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.consented_at'))
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('expires_at')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.expires_at'))
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('preferences')
                            ->label(__('cookie-consent::cookie_consent.consents.fields.preferences'))
                            ->getStateUsing(function (CookieConsentModel $record): string {
                                $lines = [];
                                foreach ($record->preferences as $category => $accepted) {
                                    $status = $accepted ? '✓' : '✗';
                                    $lines[] = "{$status} {$category}";
                                }

                                return implode("\n", $lines);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
