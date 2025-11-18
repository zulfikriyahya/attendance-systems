<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Enums\ThemeMode;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use App\Filament\Pages\DashboardAdmin;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Auth\LoginCustom;
use App\Filament\Resources\UserResource;
use Filament\Http\Middleware\Authenticate;
use App\Filament\Pages\Auth\EditProfileCustom;
use Illuminate\Session\Middleware\StartSession;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->spa()
            ->breadcrumbs(false)
            ->topNavigation()
            ->navigationGroups([
                'Data Master',
                'Data Pegawai',
                'Data Siswa',
                'Kartu Presensi',
            ])
            ->navigationItems([
                NavigationItem::make('Whatsapp')
                    ->url('https://wapi-1.zedlabs.id', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    // ->group('External')
                    ->visible(fn (): bool => Auth::user()->hasRole('super_admin'))
                    ->sort(2),
                NavigationItem::make('Dokumentasi')
                    ->url('https://docs.mtsn1pandeglang.sch.id', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-book-open')
                    // ->group('External')
                    ->sort(3),
            ])
            ->login(LoginCustom::class)
            // ->passwordReset()
            ->profile(EditProfileCustom::class)
            ->globalSearch(false)
            ->maxContentWidth(MaxWidth::Full)
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            // ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->favicon(asset('/favicon.ico'))
            ->darkModeBrandLogo(asset('/images/brand-darkmode.png'))
            ->brandLogo(asset('/images/brand-lightmode.png'))
            ->brandLogoHeight('2.2rem')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Manajemen Pengguna')
                    ->url(fn (): string => UserResource::getUrl())
                    ->icon('heroicon-o-identification')
                    ->visible(fn (): bool => Auth::user()->hasRole('super_admin')),
            ])
            ->font('Lexend')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                DashboardAdmin::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // ->widgets([
            //     UserStats::class,
            //     PresensiMasukPegawaiChart::class,
            //     PresensiPulangPegawaiChart::class,
            //     PresensiMasukSiswaChart::class,
            //     PresensiPulangSiswaChart::class,
            // ])
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
