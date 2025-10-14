<?php

namespace App\Livewire;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;

use Filament\Tables\Columns as T;
use Filament\Tables\Enums\RecordActionsPosition;

class ContactPurchaseOrderHistory extends Component implements HasTable, HasActions, HasSchemas
{
    use InteractsWithTable, InteractsWithSchemas, InteractsWithActions;

    public ?Contact $contact;

    public function mount(Contact $contact): void
    {
        $this->contact = $contact;
    }

    protected function getTableQuery(): Builder|Relation|null
    {
        return $this->contact?->purchaseOrders();
    }

    public function render()
    {
        return view('livewire.contact-purchase-order-history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                T\TextColumn::make('order_status')
                    ->label('Status')
                    ->sortable(),

                T\TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),

                T\TextColumn::make('order_date')
                    ->label('Order Date')
                    ->date('d/m/Y')
                    ->sortable(),

                T\TextColumn::make('total_value')
                    ->label('Total Value')
                    ->money(fn(PurchaseOrder $record) => $record->currency)
                    ->sortable(),
            ])
            ->defaultSort('order_date', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('viewOrder')
                    ->label('View Order')
                    ->color('teal')
                    ->icon(Heroicon::Eye)
                    ->url(fn(PurchaseOrder $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ], RecordActionsPosition::AfterColumns)
            ->toolbarActions([]);
    }
}
