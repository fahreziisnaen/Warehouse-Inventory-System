<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\StatsOverview;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('images/favicon.ico'))
            ->topNavigation(false)
            ->brandLogo(false)
            ->brandName('Warehouse Inventory System')
            ->renderHook(
                'panels::auth.header',
                fn () => '
                    <div class="flex flex-col items-center space-y-4 mb-4">
                        <img src="' . asset('images/logo.png') . '" alt="Logo" class="h-24 w-auto">
                        <div class="text-center">
                            <div class="text-2xl font-bold">Warehouse Inventory System</div>
                            <div class="text-sm">PT. Internet Pratama Indonesia</div>
                        </div>
                    </div>
                '
            )
            ->renderHook(
                'panels::footer',
                fn (): string => new HtmlString('
                    <div class="flex items-center justify-center px-4 py-3 bg-gray-50 dark:bg-gray-900">
                        <span class="text-sm text-gray-600 dark:text-gray-300 flex items-center gap-2">
                            © ' . date('Y') . ' PT. Internet Pratama Indonesia. All rights reserved. Developed by 
                            <a href="https://fahrezifauzan.vercel.app/" target="_blank" class="text-blue-500 hover:underline flex items-center gap-1">    
                                <img src="' . asset('images/frz_sign.png') . '" alt="FRZ Logo" class="h-4 w-auto"> 
                            </a>
                        </span>
                    </div>
                ')
            )
            ->colors([
                'primary' => Color::Amber,
                'secondary' => Color::Gray,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Rose,
                'info' => Color::Blue,
                'purple' => Color::Purple,
                'gray' => Color::Gray,
                'rose' => Color::Rose,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Master Data'),
                NavigationGroup::make()
                    ->label('Transaksi'),
                NavigationGroup::make()
                    ->label('Laporan'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\Reports\InventoryReport::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                StatsOverview::class,
            ])
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
            ])
            ->darkMode();
    }
}
