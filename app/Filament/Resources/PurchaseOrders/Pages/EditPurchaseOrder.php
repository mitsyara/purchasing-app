<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\PurchaseOrder\PurchaseOrderService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions as A;

class EditPurchaseOrder extends EditRecord
{
    use \Howdu\FilamentRecordSwitcher\Filament\Concerns\HasRecordSwitcher {
        afterSave as recordSwitcherAfterSave;
    }

    protected function modifyRecordSwitcherQuery(Builder $query, ?string $search): Builder
    {
        return $query->where('order_status', '!=', \App\Enums\OrderStatusEnum::Canceled);
    }

    protected static string $resource = PurchaseOrderResource::class;

    #[\Livewire\Attributes\On('refresh-order-status')]
    public function refreshOrderStatus(): void
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            // A\ViewAction::make(),
            A\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        // Get Purchase Order
        $record = $this->getRecord();

        // Use service to handle business logic
        app(PurchaseOrderService::class)->syncOrderInfo($record->id);

        $this->recordSwitcherAfterSave();
    }
}
