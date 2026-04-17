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
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use AlizHarb\ActivityLog\ActivityLogPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $isMfaEnabled = (bool) config('filament.mfa.enabled', true);
        $isMfaRequired = $isMfaEnabled && (bool) config('filament.mfa.required', false);

        return $panel
            ->default()
            ->id('admin')
            ->authGuard('web')
            ->path('admin')
            ->login()
            ->profile()
            ->multiFactorAuthentication(
                $isMfaEnabled ? [
                    AppAuthentication::make()
                        ->recoverable(),
                    EmailAuthentication::make(),
                ] : [],
                isRequired: $isMfaRequired,
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
            ])->plugins([
                ActivityLogPlugin::make()
                    ->label('Log')
                    ->pluralLabel('Logs')
                    ->navigationGroup('System')
//                    ->cluster('System'), // Optional: Group inside a cluster
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
                    ->group('Tools')->visible(fn(): bool => Auth::user()?->role === 'admin'),
                NavigationItem::make('Telescope')
                    ->url('/' . config('telescope.path', 'telescope'))
                    ->icon('heroicon-o-eye')
                    ->openUrlInNewTab()
                    ->badge('Debug', color: 'warning')
                    ->group('Tools')
                    ->visible(fn(): bool => Auth::user()?->role === 'admin'),
                NavigationItem::make('Pulse')
                    ->url('/' . ltrim((string)config('pulse.path', 'pulse'), '/'))
                    ->icon('heroicon-o-heart')
                    ->openUrlInNewTab()
                    ->badge('Pulse', color: 'danger')
                    ->group('Tools')
                    ->visible(fn(): bool => Auth::user()?->role === 'admin'),
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
