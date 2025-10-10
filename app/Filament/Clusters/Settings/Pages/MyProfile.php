<?php

namespace App\Filament\Clusters\Settings\Pages;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HigherOrderTapProxy;
use Jenssegers\Agent\Agent;

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Schemas\Components as S;
use Illuminate\Support\Facades\Hash;

class MyProfile extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected string $view = 'filament.clusters.settings.pages.my-profile';
    protected string $listView = 'livewire.components.browser-sessions-list';

    protected static ?string $cluster = SettingsCluster::class;

    public ?User $user = null;
    public array $data = [];
    public array $personalDataColumns = ['name', 'email', 'phone', 'dob'];

    public function mount(): void
    {
        // \Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo::class;
        $this->user = auth()->user();
        $this->form->fill($this->user->only($this->personalDataColumns));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                S\Fieldset::make()->schema([$this->personalInfoFields()])->columns(1),

                S\Fieldset::make()->schema([$this->securityInfoFields()])->columns(1),

                S\Fieldset::make()->schema([
                    S\Section::make('Two-Factor Authentication')
                        ->description('Add an extra layer of security to your account.')
                        ->schema([
                            //
                        ])
                        ->footer([])
                        ->aside()
                        ->compact(),
                ])->columns(1),

                S\Fieldset::make()->schema([
                    S\Section::make('Sanctum API Tokens')
                        ->description('Manage your API tokens.')
                        ->schema([
                            //
                        ])
                        ->footer([])
                        ->aside()
                        ->compact(),
                ])->columns(1),

                S\Fieldset::make()->schema([
                    S\Section::make('Browser Sessions')
                        ->description('Manage your active browser sessions.')
                        ->schema([
                            F\ViewField::make('browserSessions')
                                ->label(__('filament-breezy::default.profile.browser_sessions.label'))
                                ->hiddenLabel()
                                ->view($this->listView)
                                ->viewData(['data' => self::getSessions()]),

                            Action::make('deleteBrowserSessions')
                                ->label(__('Log Out Other Browser Sessions'))
                                ->requiresConfirmation()
                                ->modalHeading(__('Log Out Other Browser Sessions'))
                                ->modalDescription(__('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.'))
                                ->schema([
                                    F\TextInput::make('password')
                                        ->password()
                                        ->revealable()
                                        ->label(__('filament-breezy::default.fields.password'))
                                        ->required(),
                                ])
                                ->action(function (array $data) {
                                    self::logoutOtherBrowserSessions($data['password']);
                                })
                                // ->disabled(count(self::getSessions()) <= 1)
                                ->color(count(self::getSessions()) <= 1 ? 'gray' : 'primary')
                                ->modalWidth('sm'),
                        ])
                        ->footer([])
                        ->aside()
                        ->compact(),
                ])->columns(1),
            ])
            ->statePath('data');
    }

    /**
     * Personal info Form
     */
    public function personalInfoFields(): S\Section
    {
        return S\Section::make('Profile Information')
            ->description('Update your account\'s profile information and email address.')
            ->schema([
                F\TextInput::make('name')
                    ->label(__('filament-breezy::default.fields.name'))
                    ->required(),

                F\TextInput::make('email')
                    ->label(__('filament-breezy::default.fields.email'))
                    ->email()
                    ->unique()
                    ->required(),

                F\TextInput::make('phone')
                    ->label(__('Phone Number'))
                    ->tel(),

                F\DatePicker::make('dob')
                    ->label(__('Date of Birth'))
                    ->maxDate(now()->subYears(18))
                    ->minDate(now()->subYears(60)),

                S\Group::make([
                    Action::make('submitPersonalInfo')
                        ->label(__('Update'))
                        ->button()
                        ->action(function (Action $action) {
                            $this->submitPersonalInfo();
                            $action->shouldClose();
                        }),
                ])
                    ->columnSpanFull(),
            ])
            ->columns([
                'default' => 1,
                'sm' => 2,
                'md' => 1,
                'lg' => 2,
                '2xl' => 4,
            ])
            ->footer([])
            ->aside()
            ->compact()
            ->collapsible();
    }
    /**
     * Personal info form submit handler
     */
    public function submitPersonalInfo(): void
    {
        $data = collect($this->form->getState())
            ->only($this->personalDataColumns)
            ->filter();

        if ($data) {
            $this->user->update($data->toArray());
            Notification::make()
                ->title('Profile updated')
                ->success()
                ->send();
        };
    }

    /**
     * Security info Form
     */
    public function securityInfoFields(): S\Section
    {
        return S\Section::make('Password & PIN')
            ->description('Update your account\'s password and other security settings.')
            ->schema([
                F\TextInput::make('new_password')
                    ->label(__('filament-breezy::default.fields.new_password'))
                    ->password()
                    ->rules(['min:8'])
                    ->autocomplete('new-password'),

                F\TextInput::make('new_password_confirmation')
                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                    ->password()
                    ->same('new_password')
                    ->requiredWith('new_password'),


                F\TextInput::make('current_password')
                    ->label(__('filament-breezy::default.password_confirm.current_password'))
                    ->password()
                    ->revealable()
                    ->rule('current_password')
                    ->required(),

                F\TextInput::make('lock_pin')
                    ->label(__('Lock PIN'))
                    ->password()
                    ->revealable()
                    ->helperText(auth()->user()->lock_pin
                        ? __('Leave blank to keep current PIN')
                        : __('Set PIN to enable screen lock'))
                    ->regex('/^\d{6,8}$/')
                    ->autocomplete('off'),

                S\Group::make([
                    Action::make('submitSecurityInfo')
                        ->label(__('Update'))
                        ->button()
                        ->action(function (Action $action) {
                            $this->submitSecurityInfo();
                            $action->close();
                        })
                        ->requiresConfirmation(),
                ])
                    ->columnSpanFull(),
            ])
            ->columns([
                'default' => 1,
                'sm' => 2,
                'md' => 1,
                'lg' => 2,
                '2xl' => 4,
            ])
            ->footer([])
            ->aside()
            ->compact()
            ->collapsible();
    }
    /**
     * Security info form submit handler
     */
    public function submitSecurityInfo(): void
    {
        $changes = [];

        $data = collect($this->form->getState())
            ->only(['password', 'new_password', 'lock_pin'])
            ->filter()
            ->toArray();

        if (empty($data)) return;

        // Update password (if any)
        if (!empty($data['new_password'])) {
            $newPassword = $data['new_password'];
            $this->user->update([
                'password' => Hash::make($newPassword),
            ]);
            $changes[] = 'password';
        }

        // Update lock PIN (if any)
        if (!empty($data['lock_pin'])) {
            $newPin = $data['lock_pin'];
            $currentPin = $this->user->lock_pin;

            if ($currentPin && Hash::check($newPin, $currentPin)) {
                Notification::make()
                    ->danger()
                    ->title(__('New PIN cannot be the same as the current'))
                    ->send();
            } else {
                $this->user->update([
                    'lock_pin' => Hash::make($newPin),
                ]);
                $changes[] = 'lock_pin';
            }
        }

        // Hiển thị thông báo kết quả
        if (empty($changes)) {
            Notification::make()
                ->warning()
                ->title(__('No changes made'))
                ->send();
            return;
        }

        if (count($changes) === 2) {
            Notification::make()
                ->title(__('Password and PIN updated'))
                ->success()
                ->send();
        } elseif (in_array('password', $changes)) {
            Notification::make()
                ->title(__('Password updated'))
                ->success()
                ->send();
        } elseif (in_array('lock_pin', $changes)) {
            Notification::make()
                ->title(__('PIN updated'))
                ->success()
                ->send();
        }
    }


    // Session management

    /**
     * Get all of the current user's sessions.
     */
    public static function getSessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $sessions = DB::connection(config('session.connection'))->table(config('session.table'))
            ->where('user_id', Auth::user()->getAuthIdentifier())
            ->latest('last_activity')
            ->get();

        return $sessions->map(function (object $session): object {
            $agent = tap(new Agent, fn($agent) => $agent->setUserAgent($session->user_agent));

            return (object) [
                'device' => [
                    'browser' => $agent->browser(),
                    'desktop' => $agent->isDesktop(),
                    'mobile' => $agent->isMobile(),
                    'tablet' => $agent->isTablet(),
                    'platform' => $agent->platform(),
                ],
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === request()->session()->getId(),
                'last_active' => \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        })->toArray();
    }

    public static function logoutOtherBrowserSessions($password): void
    {
        if (! Hash::check($password, Auth::user()->password)) {
            Notification::make()
                ->danger()
                ->title(__('Incorrect Password'))
                ->send();

            return;
        }

        Auth::logoutOtherDevices($password);

        request()->session()->put([
            'password_hash_' . Auth::getDefaultDriver() => Auth::user()->getAuthPassword(),
        ]);

        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table'))
                ->where('user_id', Auth::user()->getAuthIdentifier())
                ->where('id', '!=', request()->session()->getId())
                ->delete();
        }

        Notification::make()
            ->success()
            ->title(__('Logout Successful'))
            ->body(__('All other browser sessions have been logged out.'))
            ->send();
    }
}
