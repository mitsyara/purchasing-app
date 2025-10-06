<?php

namespace App\Livewire;

use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;

use Filament\Tables\Columns as T;
use Filament\Tables\Enums\RecordActionsPosition;

class PurchaseShipmentProductTable extends Component implements HasTable, HasActions, HasSchemas
{
    use InteractsWithTable, InteractsWithSchemas, InteractsWithActions;

    public function render()
    {
        return view('livewire.purchase-shipment-product-table');
    }

    public ?PurchaseShipment $shipment;

    public function mount(PurchaseShipment $shipment): void
    {
        $this->shipment = $shipment;
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return $this->shipment?->purchaseShipmentLines();
    }

    public function table(Table $table): Table
    {
        $currency = $this->shipment?->currency;
        return $table
            ->columns([
                T\TextColumn::make('product.product_full_name')
                    ->label('Product')
                    ->sortable(),

                T\TextColumn::make('qty')
                    ->label('Quantity')
                    ->suffix(fn(PurchaseShipmentLine $record): string => ' ' . $record->product?->product_uom)
                    ->numeric()
                    ->sortable(),

                T\TextColumn::make('break_price')
                    ->label('Break Price')
                    ->money('VND')
                    ->sortable(),

                T\TextColumn::make('display_contract_price')
                    ->label('Contract Price')
                    ->money($currency)
                    ->description(fn(PurchaseShipmentLine $record) => $record->getFormatedUnitPrice())
                    ->color(fn(PurchaseShipmentLine $record): ?string
                    => match (true) {
                        $record->unit_price > $record->display_contract_price => 'danger',
                        $record->unit_price < $record->display_contract_price => 'success',
                        default => null,
                    })
                    ->sortable(),

                T\TextColumn::make('contract_value')
                    ->label('Value')
                    ->money($currency)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
