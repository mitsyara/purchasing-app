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

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Schemas\Components as S;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PasswordSection extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;
    public ?User $user = null;

    protected Width|string $width = Width::Medium;

    public function mount(): void
    {
        $this->user = auth()->user();
    }

    /**
     * Security info Form
     */
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Section::make('Password & PIN')
                ->description('Update your account\'s password and other security settings.')
                ->schema([
                    S\Group::make([
                        Action::make('changePassword')
                            ->schema([
                                $this->currentPasswordField(),
                                F\TextInput::make('new_password')
                                    ->label(__('New Password'))
                                    ->password()
                                    ->rules(['min:8'])
                                    ->autocomplete('new-password')
                                    ->required(),

                                F\TextInput::make('new_password_confirmation')
                                    ->label(__('New Password Confirmation'))
                                    ->password()
                                    ->same('new_password')
                                    ->required(),
                            ])
                            ->action(fn(array $data) => $this->submitPassword($data))
                            ->modal()->link()->outlined()
                            ->icon(Heroicon::LockClosed)
                            ->modalWidth($this->width),

                        Action::make('setLockPin')
                            ->label(fn(): string => $this->user->lock_pin
                                ? __('Change Lock PIN') : __('Set Lock PIN'))
                            ->schema([
                                $this->currentPasswordField(),
                                F\TextInput::make('lock_pin')
                                    ->regex('/^\d{6}$/')
                                    ->mask(\Filament\Support\RawJs::make(<<<'JS'
                                        '999999'
                                    JS))
                                    ->placeholder('999999')
                                    ->stripCharacters([' '])
                                    ->autocomplete('off')
                                    ->belowContent([
                                        __('Required to use the lock screen feature.'),
                                    ])
                                    ->required(),
                            ])
                            ->action(fn(array $data) => $this->submitLockPin($data))
                            ->modal()->link()->outlined()->color('info')
                            ->icon(fn() => $this->user->lock_pin ? Heroicon::ShieldCheck : Heroicon::ShieldExclamation)
                            ->modalWidth($this->width),

                        Action::make('removeLockPin')
                            ->label(__('Remove Lock PIN'))
                            ->color(fn(): string => $this->user->lock_pin ? 'danger' : 'gray')
                            ->modal()->link()
                            ->icon(Heroicon::NoSymbol)
                            ->schema([$this->currentPasswordField()])
                            ->action(function (): void {
                                $this->user->update([
                                    'lock_pin' => null,
                                ]);
                                Notification::make()
                                    ->success()
                                    ->title(__('PIN removed'))
                                    ->send();
                            })
                            ->disabled(fn(): bool => $this->user->lock_pin === null)
                            ->requiresConfirmation(),

                        \Filament\Actions\Action::make('clearExportedFiles')
                            ->label(__('Clear Exported Files'))->link()
                            ->icon('heroicon-o-trash')->color('danger')
                            ->action(fn() => $this->deleteFilamentExportedFiles())
                            ->visible(fn() => optional($this->user->isAdmin()))
                            ->requiresConfirmation(),

                    ])
                        ->columns(['default' => 4])
                ])
                ->aside()
                ->compact()
                ->collapsible()
        ]);
    }

    public function currentPasswordField(): F\TextInput
    {
        return F\TextInput::make('current_password')
            ->label(__('Current Password'))
            ->password()
            ->revealable()
            ->rule('current_password')
            ->required();
    }

    /**
     * Security info form submit handler
     */
    public function submitPassword(array $data): void
    {
        $data = collect($data)
            ->only(['password', 'new_password'])
            ->filter()
            ->toArray();

        if (empty($data)) return;

        // Update password (if any)
        if (!empty($data['new_password'])) {
            $newPassword = $data['new_password'];
            if (Hash::check($newPassword, $this->user->password)) {
                Notification::make()
                    ->danger()
                    ->title(__('Cannot be the same as the current one'))
                    ->send();
                return;
            }

            $this->user->update([
                'password' => Hash::make($newPassword),
            ]);
        }
        Notification::make()
            ->title(__('Password updated'))
            ->success()
            ->send();
    }

    public function submitLockPin(array $data): void
    {
        // Update lock PIN (if any)
        if (!empty($data['lock_pin'])) {
            $newPin = $data['lock_pin'];
            $currentPin = $this->user->lock_pin;

            if ($currentPin && Hash::check($newPin, $currentPin)) {
                Notification::make()
                    ->danger()
                    ->title(__('Cannot be the same as the current one'))
                    ->send();
            } else {
                $this->user->update([
                    'lock_pin' => Hash::make($newPin),
                ]);
                Notification::make()
                    ->success()
                    ->title(__('Lock PIN updated'))
                    ->send();
            }
        }
    }

    public function deleteFilamentExportedFiles(): void
    {
        $directories = [
            storage_path('app/private/filament_exports'),
            storage_path('app/dlhq_exports'),
        ];

        $totalFiles = 0;

        foreach ($directories as $dir) {
            if (\Illuminate\Support\Facades\File::exists($dir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        \Illuminate\Support\Facades\File::delete($file->getRealPath());
                        $totalFiles++;
                    }
                }
            }
        }

        if ($totalFiles === 0) {
            \Filament\Notifications\Notification::make()
                ->title(__('No files to delete'))
                ->warning()
                ->send();
            return;
        }

        \Filament\Notifications\Notification::make()
            ->title(__('Deleted Exported Files'))
            ->body(__('All :count files have been deleted.', ['count' => $totalFiles]))
            ->success()
            ->send();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.my-profile.password-section');
    }
}
