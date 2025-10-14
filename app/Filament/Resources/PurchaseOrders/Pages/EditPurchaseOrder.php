<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Services\PurchaseOrder\CallAllPurchaseOrderServices;
use Filament\Actions as A;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
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

        // Log the user who updated the record
        if ($record->wasChanged([
            'order_status',
            'order_date',
            'order_number',
            'company_id',
            'supplier_id',
            'supplier_contract_id',
            'import_warehouse_id',
            'import_port_id',
            'staff_buy_id',
            'staff_approved_id',
            'staff_docs_id',
            'staff_declarant_id',
            'staff_sales_id',
            'etd_min',
            'etd_max',
            'eta_min',
            'eta_max',
            'is_skip_invoice',
            'incoterm',
            'currency',
            'pay_term_delay_at',
            'pay_term_days',
            'notes',
        ])) {
            $record->updateQuietly(['updated_by' => auth()->id()]);
        }

        // Call Services
        new CallAllPurchaseOrderServices($record);
    }
}
