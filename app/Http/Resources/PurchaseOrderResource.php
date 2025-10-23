<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Indicates if the resource's collection keys should be preserved.
     *
     * @var bool
     */
    public $preserveKeys = false;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_status' => $this->order_status,
            'order_date' => $this->order_date,
            'order_number' => $this->order_number,
            'order_description' => $this->order_description,
            'company' => $this->company?->company_code,
            'supplier' => $this->supplier?->contact_short_name ?? $this->supplier?->contact_code,
            'supplier_contract' => $this->supplierContract?->contact_short_name ?? $this->supplierContract?->contact_code,
            'supplier_payment' => $this->supplierPayment?->contact_short_name ?? $this->supplierPayment?->contact_code,
            'end_user' => $this->endUser?->contact_short_name ?? $this->endUser?->contact_code,

            'import_warehouse' => $this->importWarehouse?->warehouse_name,
            'import_port' => $this->importPort?->port_name,
            'staff_buy' => $this->staffBuy?->name,
            'staff_approved' => $this->staffApproved?->name,
            'staff_sales' => $this->staffSales?->name,
            'staff_docs' => $this->staffDocs?->name,
            'staff_declarant' => $this->staffDeclarant?->name,
            'staff_declarant_processing' => $this->staffDeclarantProcessing?->name,

            'etd_min' => $this->etd_min,
            'etd_max' => $this->etd_max,
            'eta_min' => $this->eta_min,
            'eta_max' => $this->eta_max,

            'is_foreign' => $this->is_foreign,
            'is_skip_invoice' => $this->is_skip_invoice,

            'incoterm' => $this->incoterm,
            'currency' => $this->currency,
            'pay_term_delay_at' => $this->pay_term_delay_at,
            'pay_term_days' => $this->pay_term_days,
            'total_value' => $this->total_value,
            'total_contract_value' => $this->total_contract_value,
            'extra_costs' => $this->extra_costs,
            'total_extra_cost' => $this->total_extra_cost,
            'total_received_value' => $this->total_received_value,
            'total_paid_value' => $this->total_paid_value,
            'notes' => $this->notes,
            'created_by' => $this->createdBy?->email,
            'updated_by' => $this->updatedBy?->email,

            'products' => PurchaseOrderProductResource::collection($this->purchaseOrderLines),
        ];
    }
}
