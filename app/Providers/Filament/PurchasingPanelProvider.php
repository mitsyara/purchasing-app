<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PurchasingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('purchasing')
            ->path('purchasing')
            ->spa()

            ->login()
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->defaultAvatarProvider(\App\Providers\UiAvatarsProvider::class)

            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
            ->databaseTransactions()
            ->unsavedChangesAlerts()

            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/css/filament/purchasing/theme.css')
            ->colors([
                'primary' => \Filament\Support\Colors\Color::Fuchsia,
                'secondary' => \Filament\Support\Colors\Color::Cyan,
                'info' => \Filament\Support\Colors\Color::Blue,
                'success' => \Filament\Support\Colors\Color::Green,
                'warning' => \Filament\Support\Colors\Color::Yellow,
                'danger' => \Filament\Support\Colors\Color::Red,
                'gray' => \Filament\Support\Colors\Color::Gray,
                ...\Filament\Support\Colors\Color::all(),
            ])
            ->navigationGroups([...static::getNavGroups()])
            ->userMenuItems([
                \Filament\Actions\Action::make('lockScreen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->action(function (): void {
                        session(['screen_locked' => true]);
                    })
                    ->requiresConfirmation(),
            ])

            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])

            ->plugins([
                // Application's Log Viewer (laravel log channels)
                \Boquizo\FilamentLogViewer\FilamentLogViewerPlugin::make()
                    ->navigationGroup('system')
                    ->navigationSort(2)
                    ->navigationIcon(Heroicon::OutlinedDocumentText)
                    ->navigationLabel(__('System Logs'))
                    ->authorize(fn(): bool =>  auth()->id() === 1),

                // Login
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    // User Profile Page & Components                
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                        slug: 'my-profile123'
                    )
                    // 2FA
                    ->enableTwoFactorAuthentication(
                        force: false,
                    )
                    // Sactum API Tokens
                    ->enableSanctumTokens()
                    // Browser Sessions
                    ->enableBrowserSessions(),

                // Shieldon Filament Spatie Roles & Permissions

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
                \App\Http\Middleware\DebugbarAuthorizationMiddleware::class,
                \App\Http\Middleware\LockSessionMiddleware::class,
            ]);
    }

    public static function getNavGroups(): array
    {
        $navGroups = [
            'purchasing' => \Filament\Support\Icons\Heroicon::OutlinedShoppingCart,
            'sales' => \Filament\Support\Icons\Heroicon::OutlinedBanknotes,
            'inventory' => \Filament\Support\Icons\Heroicon::OutlinedHomeModern,
            'other' => \Filament\Support\Icons\Heroicon::OutlinedBars3,
            'settings' => \Filament\Support\Icons\Heroicon::OutlinedCog8Tooth,
            'system' => \Filament\Support\Icons\Heroicon::OutlinedChartBar,
        ];
        return collect($navGroups)->mapWithKeys(function ($icon, $title): array {
            $label = \Illuminate\Support\Str::of($title)->headline()->toString();
            return [$title => \Filament\Navigation\NavigationGroup::make()->label(__($label))];
        })->toArray();
    }
}
