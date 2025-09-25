<?php

namespace App\Services\PurchaseShipment;

use App\Models\PurchaseShipment;
use App\Services\VcbExchangeRatesService;

class SyncShipmentInfo
{
    public function __construct(public PurchaseShipment $shipment)
    {
        /** @var \App\Models\PurchaseOrder $order */
        $order = $shipment->purchaseOrder;

        if ($order->order_status === \App\Enums\OrderStatusEnum::Draft) {
            $order->updateQuietly([
                'order_status' => \App\Enums\OrderStatusEnum::Inprogress,
            ]);
        }

        // Update exchange rate based on Shipment Declaration Date
        if (!$shipment->is_exchange_rate_final) {
            if ($shipment->currency !== 'VND') {
                if ($shipment->customs_clearance_date) {
                    $date = $shipment->customs_clearance_date;
                    $exchangeRate = VcbExchangeRatesService::fetch($date->format('Y-m-d'))[$order->currency][VCB_RATE_TARGET];
                }
                // Then declaration date
                else if ($shipment->customs_declaration_date && !$shipment->customs_clearance_date) {
                    $date = $shipment->customs_declaration_date;
                    $exchangeRate = VcbExchangeRatesService::fetch($date->format('Y-m-d'))[$order->currency][VCB_RATE_TARGET];
                }

                $shipment->updateQuietly([
                    'exchange_rate' => $exchangeRate ?? null,
                ]);
            } else {
                $shipment->updateQuietly([
                    'exchange_rate' => 1,
                ]);
            }
        }

        $shipment->updateQuietly([
            'company_id' => $order->company_id,
            'currency' => $order->currency,
            'staff_buy_id' => $order->staff_buy_id,

            'supplier_id' => $order->supplier_id,
            'supplier_contract_id' => $order->supplier_contract_id,
            'supplier_payment_id' => $order->supplier_payment_id,
        ]);
    }
}
