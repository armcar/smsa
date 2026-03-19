<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use App\Filament\Resources\ActiveMusicianResource;
use App\Filament\Resources\InactiveMusicianResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\QuotaChargeResource;
use App\Filament\Resources\QuotaYearResource;
use App\Filament\Resources\SocioResource;
use App\Filament\Resources\SocioTypeResource;
use App\Filament\Resources\WpApplicationResource;
use App\Filament\Widgets\PendingPaymentsByYearChart;
use App\Filament\Widgets\ReceiptsByMonthChart;
use App\Filament\Widgets\ReceiptsStatsOverview;
use App\Filament\Widgets\MembershipStatsOverview;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->domain(env('FILAMENT_DOMAIN', 'admin.smsa.test'))
            ->path('')
            ->login()
            ->brandName('SMSA - Portal Administrativo')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Associados e Musicos')
                    ->icon('heroicon-o-users'),
            ])
            ->resources([
                SocioTypeResource::class,
                SocioResource::class,
                ActiveMusicianResource::class,
                InactiveMusicianResource::class,
                QuotaYearResource::class,
                PaymentResource::class,
                QuotaChargeResource::class,
                WpApplicationResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                ReceiptsStatsOverview::class,
                ReceiptsByMonthChart::class,
                PendingPaymentsByYearChart::class,
                MembershipStatsOverview::class,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): HtmlString => new HtmlString(
                    '<link rel="manifest" href="' . asset('manifest.webmanifest') . '">' .
                    '<meta name="theme-color" content="#0f172a">' .
                    '<meta name="apple-mobile-web-app-capable" content="yes">' .
                    '<meta name="apple-mobile-web-app-status-bar-style" content="default">' .
                    '<meta name="apple-mobile-web-app-title" content="SMSA Admin">' .
                    '<link rel="apple-touch-icon" href="' . asset('images/logo.png') . '">' .
                    '<link rel="stylesheet" href="' . asset('filament/panel-contrast.css') . '">' .
                    '<link rel="stylesheet" href="' . asset('filament/loading-spinner.css') . '">' .
                    '<style>
                        html,
                        body,
                        .filament-app-shell,
                        .filament-panel,
                        .filament-main,
                        .filament-main-content,
                        .filament-sidebar {
                            background: #e8eff8 !important;
                            min-height: 100vh;
                        }
                        .filament-app-shell {
                            background-attachment: fixed;
                        }
                        .filament-sidebar {
                            position: sticky;
                            top: 0;
                        }
                        .smsa-admin-footer {
                            margin-top: 24px;
                            padding: 10px 14px 16px;
                            text-align: center;
                            font-size: 12px;
                            color: rgba(15, 23, 42, .72);
                            background: #fefefe;
                            border-top: 1px solid rgba(15, 23, 42, .08);
                            box-shadow: inset 0 1px 0 rgba(15, 23, 42, .05);
                        }
                    </style>' .
                    '<script src="' . asset('filament/flatpickr-pt.js') . '"></script>'
                )
            )
            ->renderHook(
                'panels::body.end',
                fn (): HtmlString => new HtmlString(
                    '<footer class="smsa-admin-footer">Desenvolvimento Web &copy; 2026: Armando Carvalho</footer>' .
                    '<script src="' . asset('filament/loading-spinner.js') . '"></script>' .
                    '<script src="' . asset('filament/livewire-419-handler.js') . '"></script>' .
                    '<script src="' . asset('filament/pwa-register.js') . '"></script>'
                )
            )

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
