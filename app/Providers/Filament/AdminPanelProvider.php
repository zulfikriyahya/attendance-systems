<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfileCustom;
use App\Filament\Pages\Auth\LoginCustom;
use App\Filament\Pages\DashboardAdmin;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\PresensiMasukPegawaiChart;
use App\Filament\Widgets\PresensiMasukSiswaChart;
use App\Filament\Widgets\PresensiPulangPegawaiChart;
use App\Filament\Widgets\PresensiPulangSiswaChart;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('presensi')
            ->default()
            ->spa()
            ->breadcrumbs(false)
            ->topNavigation()
            ->login(LoginCustom::class)
            ->passwordReset()
            ->profile(EditProfileCustom::class)
            ->globalSearch(false)
            ->maxContentWidth(MaxWidth::Full)
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->defaultThemeMode(ThemeMode::Dark)
            ->favicon(asset('/favicon.ico'))
            ->darkModeBrandLogo(asset('/images/brand-darkmode.png'))
            ->brandLogo(asset('/images/brand-lightmode.png'))
            ->brandLogoHeight('2.6rem')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Manajemen Pengguna')
                    ->url(fn (): string => UserResource::getUrl())
                    ->icon('heroicon-o-identification')
                    ->visible(fn (): bool => Auth::user()->username === 'administrator'),
            ])
            ->font('Ubuntu')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                DashboardAdmin::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                PresensiMasukPegawaiChart::class,
                PresensiPulangPegawaiChart::class,
                PresensiMasukSiswaChart::class,
                PresensiPulangSiswaChart::class,
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
            ->theme(asset('css/filament/admin/theme.css'))
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),

                EasyFooterPlugin::make()
                    ->withFooterPosition('footer'),

                AuthUIEnhancerPlugin::make()
                    ->formPanelPosition('left')
                    ->formPanelWidth('45%')
                    ->emptyPanelBackgroundImageUrl('/images/wallpaper.png')
                    ->emptyPanelBackgroundColor(Color::hex('#d1efd7'))
                    ->showEmptyPanelOnMobile(false),
            ]);
    }
}
