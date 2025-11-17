<?php

namespace App\Providers\Filament;

use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

use Filament\Support\Enums\Width;

class PurchasingPanelProvider extends PanelProvider
{
    public function panel(\Filament\Panel $panel): \Filament\Panel
    {
        return $panel
            ->default()
            ->id('purchasing')
            ->path('purchasing')
            ->homeUrl('/')
            ->spa(hasPrefetching: true)
            // ->spaUrlExceptions([
            //     '*/admin/posts/*',
            // ])

            ->login()
            ->passwordReset()
            ->emailVerification()
            ->emailChangeVerification()
            ->defaultAvatarProvider(\App\Providers\UiAvatarsProvider::class)

            ->multiFactorAuthentication([
                \Filament\Auth\MultiFactor\App\AppAuthentication::make()
                    ->recoverable(),
            ])

            ->globalSearch(false)
            ->broadcasting(false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')

            ->databaseTransactions()
            ->unsavedChangesAlerts()

            // ->errorNotifications(false)
            // ->registerErrorNotification(
            //     title: 'An error occurred',
            //     body: 'Please try again later.',
            // )
            // ->registerErrorNotification(
            //     title: 'Record not found',
            //     body: 'The resource you are looking for does not exist.',
            //     statusCode: 404,
            // )

            ->topNavigation()
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/css/filament/purchasing/theme.css')
            // ->assets([
            //     \Filament\Support\Assets\Css::make('custom-stylesheet', resource_path('css/custom.css')),
            //     \Filament\Support\Assets\Js::make('custom-script', resource_path('js/custom.js')),
            // ])
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
            ->navigationGroups([
                ...$this->getNavGroups(),
            ])

            ->userMenu(true, \Filament\Enums\UserMenuPosition::Topbar)
            ->userMenuItems([
                'profile' => fn(\Filament\Actions\Action $action) => $action->url(
                    \App\Filament\Clusters\Settings\Pages\MyProfile::getUrl(),
                ),

                \Filament\Actions\Action::make('lockScreen')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->action(function (): void {
                        session(['screen_locked' => true]);
                    })
                    ->visible(fn() => isset(auth()->user()->lock_pin))
                    ->requiresConfirmation(),
            ])

            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                \Filament\Widgets\AccountWidget::class,
                \Filament\Widgets\FilamentInfoWidget::class,
            ])

            ->plugins([
                // Shield add-on Spatie Roles & Permissions
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->registerNavigation(false)
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                        '2xl' => 4,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                    ]),

                \AchyutN\FilamentLogViewer\FilamentLogViewer::make()
                    ->navigationGroup('system')
                    ->navigationLabel(__('System Logs'))
                    ->navigationIcon('heroicon-o-document-text')
                    ->navigationSort(2)
                    ->navigationUrl('/application-logs')
                    ->pollingTime(null)
                    ->authorize(fn() => auth()->user()->isAdmin()),


                // Record switcher
                \Howdu\FilamentRecordSwitcher\FilamentRecordSwitcherPlugin::make(),

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


    // Helpers

    public function getNavGroups(): array
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
            return [
                $title => \Filament\Navigation\NavigationGroup::make()->label(__($label))
            ];
        })->toArray();
    }
}
