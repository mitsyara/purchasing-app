<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product?->product_full_name,
            'product_code' => $this->product?->product_code,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'contract_price' => $this->contract_price,
        ];
    }
}
