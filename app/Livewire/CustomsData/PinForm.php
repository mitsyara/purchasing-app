<?php

namespace App\Livewire\CustomsData;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

use Filament\Forms\Components as F;
use Filament\Schemas\Components as S;
use Illuminate\Support\Facades\Cache;

class PinForm extends Component implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions;

    public int|string|null $pin;

    public function render()
    {
        return view('livewire.customs-data.pin-form');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            S\Flex::make([
                F\TextInput::make('pin')
                    ->label('Enter PIN to continue')
                    ->autocomplete('off')
                    ->placeholder('Enter PIN')
                    ->mask('9999')
                    ->password()
                    ->length(4)
                    ->rules([
                        fn(): \Closure => function (string $attribute, $value, \Closure $fail) {
                            $correctPin = Cache::get('app_pin', '1234');
                            if ((string) $value !== $correctPin) {
                                $fail(__('Invalid PIN code. Contact admin for assistance.'));
                            }
                        },
                    ])
                    ->required(),

            ])

        ])->columns(['default' => 1]);
    }

    public function submit()
    {
        $correctPin = Cache::get('app_pin', '1234');
        $data = $this->form->getState();
        $inputPin = (string) ($data['pin'] ?? '');
        if ($inputPin === $correctPin) {
            session()->put('pin_verified', true);
            return redirect()->route('index');
        }
    }
}
