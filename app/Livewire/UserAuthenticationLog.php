<?php

namespace App\Livewire;

use Livewire\Component;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;

class UserAuthenticationLog extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function render()
    {
        return view('livewire.user-authentication-log');
    }

    #[\Livewire\Attributes\On('refresh-custom-table')]
    public function updateTable(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Session::query()
            )
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => auth()->id() !== 1
                    ? $query->where('user_id', auth()->id())
                    : $query
            )
            ->columns([
                __index(),

                T\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('user_agent')
                    ->label(__('User Agent'))
                    ->toggleable(),

                T\TextColumn::make('timestamp')
                    ->label(__('Last Activity'))
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(query: fn(Builder $query, string $direction): Builder
                    => $query->orderBy('last_activity', $direction))
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ]);
    }
}
