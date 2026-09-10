<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\HtmlString;
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
            ->brandName('CAAP-DTS')
            ->brandLogo(new HtmlString(
                '<span style="display:flex;align-items:center;gap:0.6rem;height:100%;">'
                . '<span style="display:inline-flex;align-items:center;justify-content:center;height:100%;aspect-ratio:1;background:#fff;border-radius:0.6rem;padding:0.18rem;box-shadow:0 1px 2px rgba(0,0,0,.08);">'
                . '<img src="' . asset('img/caap-logo.png') . '" alt="CAAP-DTS" style="height:100%;width:auto;" />'
                . '</span>'
                . '<span style="font-weight:800;letter-spacing:-0.02em;">CAAP&#8209;DTS</span>'
                . '</span>'
            ))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('img/favicon.png'))
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->navigationGroups([
                NavigationGroup::make('Records')
                    ->icon('heroicon-o-document-text'),
                NavigationGroup::make('Directory')
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make('Settings')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                AccountWidget::class,
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
            ]);
    }
}
