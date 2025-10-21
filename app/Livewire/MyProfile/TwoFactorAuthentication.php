<?php

namespace App\Livewire\MyProfile;

use Livewire\Component;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Auth\MultiFactor\App\AppAuthentication;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;

class TwoFactorAuthentication extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    protected string $description = 'Manage your multi-factor authentication settings. 
    When multi-factor authentication is enabled, you will be prompted for a secure, random token during authentication.';

    protected function getProvider(): AppAuthentication
    {
        return app(AppAuthentication::class)->recoverable();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                S\Section::make('Multi-Factor Authentication')
                    ->description($this->description)
                    ->schema([
                        S\Flex::make([
                            ...$this->getProvider()->getManagementSchemaComponents(),
                        ]),
                    ])
                    ->footer([])
                    ->aside()
                    ->compact()
            ]);
    }

    public function render()
    {
        return view('livewire.my-profile.two-factor-authentication');
    }
}
