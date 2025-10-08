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
use Filament\Infolists\Components as I;

class UserActivityLog extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    public function render()
    {
        return view('livewire.user-activity-log');
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
                \App\Models\Activity::query()
                    ->leftJoin('users', function ($join) {
                        $join->on('users.id', '=', 'activity_log.causer_id')
                            ->where('activity_log.causer_type', '=', \App\Models\User::class);
                    })
                    ->select('activity_log.*', 'users.name as user_name')
            )
            ->modifyQueryUsing(
                fn(Builder $query): Builder
                => auth()->id() !== 1
                    ? $query->where('causer_type', \App\Models\User::class)->where('causer_id', auth()->id())
                    : $query
            )
            ->columns([
                __index(),

                T\TextColumn::make('log_name')
                    ->label('Type')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                T\TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn(string $state): ?string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => null,
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('event_label')
                    ->getStateUsing(fn($record) => $record->getLabel())
                    ->sortable(
                        query: fn(Builder $query, string $direction): Builder
                        => $query->orderBy('subject_type', $direction)
                            ->orderBy('subject_id', $direction)
                    )
                    ->toggleable(),

                T\TextColumn::make('user_name')
                    ->label('User')
                    ->sortable(condition: auth()->id() === 1)
                    ->default(__('System'))
                    ->toggleable(),

                T\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime('d M Y H:i')
                    ->timezone('Asia/Ho_Chi_Minh')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                A\Action::make('clear')
                    ->label('Clear Logs')
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function () {
                        \App\Models\Activity::query()->delete();
                        $this->dispatch('refresh-custom-table');
                    }),
            ])
            ->recordActions([
                static::viewLogDetail(),
            ]);
    }

    public static function viewLogDetail(): A\Action
    {
        return
            A\Action::make('detail')
            ->label('View Details')
            ->icon(Heroicon::Eye)
            ->color('secondary')
            ->modal()
            ->slideOver()
            ->schema(function ($record): array {
                $attributes = $record->properties['attributes'] ?? [];
                $old = $record->properties['old'] ?? [];
                $merged = collect($attributes)
                    ->union($old)
                    ->map(function ($newValue, $key) use ($old, $attributes) {
                        return [
                            'attribute' => $key,
                            'old' => $old[$key] ?? null,
                            'new' => $attributes[$key] ?? null,
                        ];
                    })
                    ->values()
                    ->toArray();

                return [
                    I\TextEntry::make('causer.name')
                        ->label('Authorize')
                        ->default(__('System'))
                        ->columnSpanFull(),

                    I\TextEntry::make('created_at')
                        ->label('Timestamp')
                        ->dateTime('d M Y H:i')
                        ->timezone('Asia/Ho_Chi_Minh')
                        ->columnSpanFull(),

                    I\KeyValueEntry::make('properties.attributes')
                        ->label('Values')
                        ->keyLabel('Attribute')
                        ->valueLabel('Value')
                        ->visible(fn($record) => $record->event === 'created')
                        ->columnSpanFull(),

                    I\KeyValueEntry::make('properties.old')
                        ->label('Original')
                        ->keyLabel('Attribute')
                        ->valueLabel('Value')
                        ->visible(fn($record) => $record->event === 'deleted')
                        ->columnSpanFull(),

                    \App\Filament\Infolists\Components\ActivityChangesTable::make('changes')
                        ->label('Changes')
                        ->state($merged)
                        ->visible(fn($record) => $record->event === 'updated')
                        ->columnSpanFull(),
                ];
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'));
    }
}
