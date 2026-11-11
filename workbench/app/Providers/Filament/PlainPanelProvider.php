<?php

declare(strict_types=1);

namespace Workbench\App\Providers\Filament;

use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Workbench\App\Filament\Pages\Invoices;
use Workbench\App\Filament\Pages\Orders;
use Workbench\App\Filament\Pages\Reports;
use Workbench\App\Filament\Widgets\ChannelShare;
use Workbench\App\Filament\Widgets\LatestOrders;
use Workbench\App\Filament\Widgets\OrdersOverview;
use Workbench\App\Filament\Widgets\RevenueChart;

/** The same demo without the plugin: Filament's own loading states, for comparison. */
final class PlainPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('plain')
            ->path('plain')
            ->brandName('Demo Shop (without the plugin)')
            ->pages([
                Dashboard::class,
                Orders::class,
                Invoices::class,
                Reports::class,
            ])
            ->widgets([
                OrdersOverview::class,
                RevenueChart::class,
                ChannelShare::class,
                LatestOrders::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
