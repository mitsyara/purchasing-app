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
use Filament\Notifications\Notification;
use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Support\Facades\DB;

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
            ->poll('30s')
            ->columns([
                __index(),

                T\TextColumn::make('id')
                    ->label(__('Session ID'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable(['name', 'email', 'phone'])
                    ->sortable(),

                T\TextColumn::make('ip_address')
                    ->label(__('IP Address'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('user_agent')
                    ->label(__('User Agent'))
                    ->toggleable(),

                T\TextColumn::make('last_activity_at')
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
                A\Action::make('signOutAll')
                    ->label(__('Sign Out All'))
                    ->icon(Heroicon::OutlinedArrowLeftOnRectangle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        try {
                            DB::transaction(function () {
                                \App\Models\Session::whereNot('id', session()->getId())
                                    ->delete();
                            });
                            Notification::make()
                                ->success()
                                ->title(__('Success'))
                                ->body(__('All other sessions have been signed out.'))
                                ->send();

                            $this->dispatch('refresh-custom-table');
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Error'))
                                ->body(__('Failed to sign out. Please try again later.'))
                                ->send();
                        }
                    })
                    ->hidden(fn(): bool => \App\Models\Session::count() <= 1),
            ])
            ->toolbarActions([
                A\BulkAction::make('signOutSelected')
                    ->label(__('Sign Out Selected'))
                    ->icon(Heroicon::OutlinedArrowLeftOnRectangle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        try {
                            DB::transaction(function () use ($records) {
                                \App\Models\Session::whereIn('id', $records->toArray())
                                    ->whereNot('id', session()->getId())
                                    ->delete();
                            });
                            Notification::make()
                                ->success()
                                ->title(__('Success'))
                                ->body(__('Selected sessions have been signed out.'))
                                ->send();

                            $this->dispatch('refresh-custom-table');
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->danger()
                                ->title(__('Error'))
                                ->body(__('Failed to sign out. Please try again later.'))
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                A\Action::make('signOut')
                    ->label(__('Sign Out'))
                    ->icon(Heroicon::OutlinedArrowLeftStartOnRectangle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (\App\Models\Session $record): void {
                        $record->delete();
                        $this->dispatch('refresh-custom-table');
                    })
                    ->disabled(fn($record) => $record->id === session()->getId()),
            ]);
    }
}
