<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\Contact;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Filament\Actions as A;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;

class ContactTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => Contact::query())
            ->columns([
                __index(),

                T\TextColumn::make('contact_name')
                    ->label(__('Name'))
                    ->description(fn(Contact $record): string => $record->contact_code . ' / ' . $record->contact_short_name)
                    ->sortable(query: fn(Builder $query, string $direction): Builder
                    => $query->orderBy('contact_name', $direction)
                        ->orderBy('contact_code', $direction)
                        ->orderBy('contact_short_name', $direction))
                    ->searchable(query: fn(Builder $query, string $search): Builder
                    => $query->where('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_short_name', 'like', "%{$search}%")
                        ->orWhere('contact_code', 'like', "%{$search}%"))
                    ->toggleable(),

                T\TextColumn::make('tax_code')
                    ->label(__('Tax Code'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('country.country_name')
                    ->label(__('Country'))
                    ->description(fn(Contact $record): string => $record->region?->getLabel())
                    ->sortable(query: fn(Builder $query, string $direction): Builder
                    => $query->orderBy('country_id', $direction)
                        ->orderBy('region', $direction))
                    ->toggleable(),

                T\TextColumn::make('company_types')
                    ->label(__('Type'))
                    ->badge()
                    ->listWithLineBreaks()
                    ->toggleable(),

                T\TextColumn::make('contact_info')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->toggleable(),

                T\TextColumn::make('rep_info')
                    ->label(__('Representative'))
                    ->description(fn(Contact $record): string => $record->rep_title)
                    ->sortable(query: fn(Builder $query, string $direction): Builder
                    => $query->orderBy('rep_name', $direction))
                    ->searchable(query: fn(Builder $query, string $search): Builder
                    => $query->where('rep_name', 'like', "%{$search}%"))
                    ->toggleable(),

            ])
            ->filters([])
            ->recordActions([
                A\ActionGroup::make([
                    A\EditAction::make()
                        ->modal()->slideOver()
                        ->modalWidth(Width::FiveExtraLarge),
                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\BulkAction::make('assignStaff')
                        ->modal()->color('success')
                        ->label(__('Assign Staff'))
                        ->action(function (Collection $records, array $data): void {
                            dd($records, $data);
                        })
                        ->requiresConfirmation()
                        ->color('secondary')
                        ->icon('heroicon-o-user-group')
                        ->deselectRecordsAfterCompletion(),
                ])
            ])
        ;
    }
}
