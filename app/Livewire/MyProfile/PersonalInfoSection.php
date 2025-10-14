<?php

namespace App\Livewire\MyProfile;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Livewire\Component;

use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Schemas\Components as S;
use Filament\Schemas\Schema;

class PersonalInfoSection extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    public ?User $user = null;
    public array $data = [];
    public array $personalDataColumns = ['name', 'email', 'phone', 'dob'];

    public function mount(): void
    {
        // \Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo::class;
        $this->user = auth()->user();
        $data = $this->user->only($this->personalDataColumns);
        $this->form->fill($data);
    }

    /**
     * Personal info Form
     */
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Section::make('Profile Information')
                ->description('Update your account\'s profile information and email address.')
                ->schema([
                    F\TextInput::make('name')
                        ->label(__('User Name'))
                        ->required(),

                    F\TextInput::make('email')
                        ->label(__('Email Address'))
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
                            ->action(fn() => $this->submitPersonalInfo()),
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
                ->collapsible(),
        ])
        ->statePath('data');
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

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.my-profile.personal-info-section');
    }
}
