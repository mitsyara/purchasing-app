<?php

namespace App\Livewire\MyProfile;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Schemas\Components as S;

class SessionSection extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Section::make('Browser Sessions')
                ->description('Manage your active browser sessions.')
                ->schema([
                    F\ViewField::make('browserSessions')
                        ->label(__('View Sessions'))
                        ->hiddenLabel()
                        // session list view
                        ->view('livewire.my-profile.browser-sessions-list')
                        ->viewData(['data' => $this->getSessions()]),

                    Action::make('deleteBrowserSessions')
                        ->label(__('Log Out Other Browser Sessions'))
                        ->requiresConfirmation()
                        ->modalHeading(__('Log Out Other Browser Sessions'))
                        ->modalDescription(__('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.'))
                        ->schema([
                            F\TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->label(__('Password Confirmation'))
                                ->rule('current_password')
                                ->required(),
                        ])
                        ->action(fn(array $data) => $this->logoutOtherBrowserSessions($data['password']))
                        ->disabled(count($this->getSessions()) <= 1)
                        ->color(fn(Action $action) => $action->isDisabled() ? 'gray' : 'primary')
                        ->modalWidth('sm'),
                ])
                ->footer([])
                ->aside()
                ->compact()
        ]);
    }

    /**
     * Get all of the current user's sessions.
     */
    public function getSessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $sessions = DB::connection(config('session.connection'))->table(config('session.table'))
            ->where('user_id', auth()->user()->getAuthIdentifier())
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

    public function logoutOtherBrowserSessions(string $password): void
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

        $this->reset();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.my-profile.session-section');
    }
}
