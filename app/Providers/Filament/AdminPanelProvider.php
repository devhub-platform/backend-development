<?php

namespace App\Providers\Filament;

use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\ContentActivityChart;
use App\Filament\Widgets\TopPostsTable;
use Filament\Navigation\NavigationItem;
use Filament\Widgets\AccountWidget;
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
            ->authGuard('web')
            ->path('admin')
            ->login()
            ->profile()
            ->multiFactorAuthentication(
                config('filament.mfa.enabled', true) ? [
                    AppAuthentication::make()
                        ->recoverable(),
                    EmailAuthentication::make(),
                ] : []
            )
            ->brandName('Admin Panel')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminOverviewStats::class,
                ContentActivityChart::class,
                TopPostsTable::class,
                AccountWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('AI Analytics')
                    ->icon('heroicon-o-sparkles')
                    ->badge('Monitor', color: 'success')
                    ->url('#')  // Links to dashboard section
                    ->group('Tools'),
                NavigationItem::make('Log Viewer')
                    ->url('/log-viewer')
                    ->icon('heroicon-o-document-text')
                    ->openUrlInNewTab()
                    ->badge('Logs', color: 'info')
                    ->group('Tools'),
                NavigationItem::make('Telescope')
                    ->url('/' . config('telescope.path', 'telescope'))
                    ->icon('heroicon-o-eye')
                    ->openUrlInNewTab()
                    ->badge('Debug', color: 'warning')
                    ->group('Tools'),
            ])
            //            ->canAccessPanel(fn ($user): bool => $user?->role === 'admin')
            ->authMiddleware([
                Authenticate::class,
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
            ]);
    }
}
