<?php

namespace App\Livewire\MyProfile;

use Livewire\Component;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Schemas\Components as S;
use Filament\Forms\Components as F;
use Filament\Support\Enums\Width;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Columns as T;

class PersonalAccessTokensSection extends Component implements HasSchemas, HasActions, HasTable
{
    use InteractsWithSchemas, InteractsWithActions, InteractsWithTable;

    public array $tokens = [];

    private string $description = <<<TXT
        Manage your personal access tokens. 
        You can create tokens for API access and revoke them as needed. 
        This allows other applications to securely access your account without sharing your password.
        TXT;

    public function mount(): void
    {
        $this->loadTokens();
    }

    protected function loadTokens(): void
    {
        $this->tokens = auth()->user()
            ->tokens()
            ->latest()
            ->get()
            ->map(fn($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => implode(',', $token->abilities ?? []),
                'created_at' => $token->created_at,
                'token' => null,
            ])
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn() => $this->tokens)
            ->columns([
                __index(),
                T\TextColumn::make('name')
                    ->label('Token Name'),
                T\TextColumn::make('abilities')
                    ->label('Abilities')
                    ->badge()
                    ->separator(','),
                T\TextColumn::make('created_at')
                    ->label('Created At')->dateTime(),
            ])
            ->recordActions([
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(fn($record) => $this->deleteToken($record['id']))
                    ->requiresConfirmation(),
            ])
            ->headerActions([
                Action::make('createToken')
                    ->label('Create New Token')
                    ->link()->color('teal')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        F\TextInput::make('name')
                            ->label('Token Name')
                            ->required(),

                        F\CheckboxList::make('abilities')
                            ->label('Abilities')
                            ->options([
                                'read' => 'Read',
                                'write' => 'Write',
                                'delete' => 'Delete',
                            ])
                            ->descriptions([
                                'read' => 'Allows API read access.',
                                'write' => 'Allows API create/write access.',
                                'delete' => 'Allows API delete access.',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $user = auth()->user();
                        $newToken = $user->createToken($data['name'], $data['abilities']);
                        $this->replaceMountedAction('displayToken', [
                            'token' => $newToken->plainTextToken
                        ]);
                    })
                    ->modalSubmitActionLabel(__('Create Token'))
                    ->modalWidth(Width::Medium),

                Action::make('delete')
                    ->label(__('Delete All Tokens'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->action(function (): void {
                        $user = auth()->user();
                        $user->tokens()->delete();

                        $this->loadTokens();
                        $this->resetTable();

                        Notification::make()
                            ->title('All tokens deleted!')
                            ->danger()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete All Tokens'))
                    ->modalDescription(__('Are you sure you want to delete all your personal access tokens? This action cannot be undone.')),

            ], HeaderActionsPosition::Bottom);
    }

    public function deleteToken($id): void
    {
        $user = auth()->user();
        $user->tokens()->where('id', $id)->delete();

        $this->loadTokens();
        $this->resetTable();

        Notification::make()
            ->title('Token deleted!')
            ->danger()
            ->send();
    }

    public function displayToken(): Action
    {
        return Action::make('displayToken')
            ->label('Your New Token')->color('teal')
            ->modalDescription(__('Please copy your new personal access token now. You won\'t be able to see it again!'))
            ->schema(fn(array $arguments) => [
                S\Text::make($arguments['token'])
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono)
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->color('neutral')
                    ->copyable(),
            ])
            ->modalWidth(Width::Medium)
            ->requiresConfirmation()
            ->after(function () {
                $this->loadTokens();
                $this->resetTable();

                Notification::make()
                    ->title('Token created!')
                    ->success()
                    ->send();
            })
            ->modalCancelAction(false)
            ->modalSubmitActionLabel(__('I\'ve Copied It!'));
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.my-profile.personal-access-tokens-section');
    }
}
