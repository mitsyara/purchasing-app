<?php

namespace App\Livewire\MyProfile;

use Livewire\Component;
use Filament\Forms\Components as F;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components as S;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;

class PersonalAccessTokensSection extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    private string $description = 'Manage your personal access tokens. 
    You can create tokens for API access and revoke them as needed. 
    This is to allow other applications to securely access your account without sharing your password.';

    public $tokens = [];
    public $newTokenName = '';
    public $newTokenAbilities = [];

    public function mount(): void
    {
        $this->loadTokens();
    }

    protected function loadTokens()
    {
        $this->tokens = auth()->user()
            ->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => implode(',', $token->abilities ?? []),
                'token' => null, // plain text chỉ hiện khi mới tạo
            ])
            ->toArray();
    }

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            S\Section::make('Personal Access Tokens')
                ->description($this->description)
                ->schema([])
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

    public function createToken()
    {
        $user = auth()->user();
        $token = $user->createToken($this->newTokenName, $this->newTokenAbilities);

        // plain text token chỉ hiện khi mới tạo
        $plainTextToken = $token->plainTextToken;
        $accessToken = $user->tokens()->latest()->first();

        // cập nhật danh sách token
        array_unshift($this->tokens, [
            'id' => $accessToken->id,
            'name' => $accessToken->name,
            'abilities' => implode(',', $accessToken->abilities ?? []),
            'token' => $plainTextToken,
        ]);

        $this->newTokenName = '';
        $this->newTokenAbilities = [];

        Notification::make()
            ->title('Token created successfully!')
            ->success()
            ->send();
    }

    public function deleteToken($id)
    {
        $user = auth()->user();
        $user->tokens()->where('id', $id)->delete();

        $this->loadTokens();

        Notification::make()
            ->title('Token deleted successfully!')
            ->success()
            ->send();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.my-profile.personal-access-tokens-section');
    }
}
