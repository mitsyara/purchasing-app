<?php

namespace App\Filament\Resources\PurchaseOrders\Tables;

use App\Models\PurchaseOrder;
use App\Services\Core\PurchaseOrderService;
use Filament\Actions as A;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

use Filament\Tables\Columns as T;
use Filament\Tables\Filters as TF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        $userId = auth()->id();
        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->orderBy('order_date', 'desc'))
            ->columns([
                __index(),

                T\TextColumn::make('order_status')
                    ->label(__('Status'))
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('order_date')
                    ->date('d/m/Y')
                    ->label(__('Order Date'))
                    ->description(fn(PurchaseOrder $record) => $record->order_number)
                    ->sortable()
                    ->searchable(query: fn(Builder $query, string $search): Builder
                    => $query->orWhere('order_number', 'like', "%{$search}%"))
                    ->toggleable(),

                T\TextColumn::make('company.company_code')
                    ->label(__('Company'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('supplier.contact_name')
                    ->label(__('Supplier'))
                    ->description(fn(PurchaseOrder $record) => $record->supplierContract?->contact_name)
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('staffBuy.name')
                    ->label(__('Purchasing Staff'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('staffDocs.name')
                    ->label(__('Docs Staff'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                T\TextColumn::make('total_value')
                    ->label(__('Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_contract_value')
                    ->label(__('Contract Value'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_received_value')
                    ->label(__('Received'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                T\TextColumn::make('total_paid_value')
                    ->label(__('Paid'))
                    ->money(fn($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

            ])
            ->filters([
                //
            ])
            ->recordActions([
                A\ActionGroup::make([
                    // Custom action
                    A\ActionGroup::make([
                        A\Action::make('processOrder')
                            ->modal()
                            ->icon(Heroicon::PlayCircle)
                            ->color(fn(A\Action $action): string
                            => match ($action->isDisabled()) {
                                true => 'gray',
                                default => 'info',
                            })
                            ->requiresConfirmation()
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('order_number')
                                    ->label(__('Order No.'))
                                    ->unique()
                                    ->required(),
                                \Filament\Forms\Components\DatePicker::make('order_date')
                                    ->label(__('Order Date'))
                                    ->maxDate(today())
                                    ->required(),
                            ])
                            ->fillForm(fn(PurchaseOrder $record): array => [
                                'order_number' => $record->order_number,
                                'order_date' => $record->order_date ?? today(),
                            ])
                            ->action(fn(array $data, PurchaseOrder $record) => 
                                app(PurchaseOrderService::class)->processOrder($record->id, $data)
                            )
                            ->disabled(fn(PurchaseOrder $record): bool => in_array($record->order_status, [
                                \App\Enums\OrderStatusEnum::Completed,
                                \App\Enums\OrderStatusEnum::Canceled,
                            ])),

                        A\Action::make('cancelOrder')
                            ->modal()
                            ->icon(Heroicon::XCircle)
                            ->color(fn(A\Action $action): string
                            => match ($action->isDisabled()) {
                                true => 'gray',
                                default => 'danger',
                            })
                            ->requiresConfirmation()
                            ->action(fn(PurchaseOrder $record) => 
                                app(PurchaseOrderService::class)->cancelOrder($record->id)
                            )
                            ->disabled(fn(PurchaseOrder $record): bool
                            => $record->order_status === \App\Enums\OrderStatusEnum::Canceled),
                    ])
                        ->dropdown(false),

                    // A\ViewAction::make(),
                    A\EditAction::make(),
                    A\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                A\BulkActionGroup::make([
                    A\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
