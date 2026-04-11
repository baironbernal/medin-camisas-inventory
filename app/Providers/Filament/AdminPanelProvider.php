<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdvancedStatsOverviewWidget;
use App\Filament\Widgets\LowSellingProducts;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\TopSellingProducts;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->colors([
                // Base UI — dark navy purple (sidebar, nav, headers)
                'primary' => Color::hex('#292944'),
                // Gray scale tinted with the brand purple (backgrounds, borders, text)
                'gray'    => Color::hex('#3E3C64'),
                // Info — soft purple accent
                'info'    => Color::hex('#3E3C64'),
                // Success — sage green that harmonises with the beige palette
                'success' => Color::hex('#4A7C59'),
                // Warning — warm amber
                'warning' => Color::hex('#C9813A'),
                // Danger — muted rose/brick
                'danger'  => Color::hex('#9B3B3B'),
            ])
            ->brandLogo(fn() => view('filament.logos.logo-light'))
            ->darkModeBrandLogo(fn() => view('filament.logos.logo-dark'))
            ->brandLogoHeight('42px')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AdvancedStatsOverviewWidget::class,
                TopSellingProducts::class,
                LowSellingProducts::class,
                LowStockWidget::class,
                Widgets\AccountWidget::class,
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
            ->navigationGroups([
                'Gestión de Tiendas',
                'Gestión de Productos',
                'Gestión de Pedidos',
                'Inventario',
                'Configuración',
                'Usuarios y Roles',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->spa();
    }
}
