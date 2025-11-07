<?php

namespace App\Services\Core;

use App\Models\PurchaseShipment;
use App\Models\PurchaseShipmentLine;
use App\Services\Core\PurchaseOrderService;
use App\Repositories\Contracts\PurchaseShipmentRepositoryInterface;

class PurchaseShipmentService
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService,
        private PurchaseShipmentRepositoryInterface $purchaseShipmentRepository
    ) {}

    /**
     * Sync all shipment information (equivalent to CallAllPurchaseShipmentServices)
     */
    public function syncShipmentInfo(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        
        // Process related order
        $this->processRelatedOrder($shipment);
        
        // Sync shipment info
        $this->syncBasicShipmentInfo($shipmentId);
        
        // Sync shipment lines info
        $this->syncShipmentLinesInfo($shipmentId);
        
        // Update shipment totals
        $this->updateShipmentTotals($shipmentId);
    }

    /**
     * Process related order (from CallAllPurchaseShipmentServices)
     */
    private function processRelatedOrder(PurchaseShipment $shipment): void
    {
        $order = $shipment->purchaseOrder;
        
        if (!$order->order_number) {
            $orderNumber = $this->purchaseOrderService->generateOrderNumber(null, $order->id);
            $order->order_number = $orderNumber;
        }
        
        if (!$order->order_date) {
            $order->order_date = now();
        }
        
        if ($order->order_status === \App\Enums\OrderStatusEnum::Draft) {
            $order->order_status = \App\Enums\OrderStatusEnum::Inprogress;
        }
        
        $order->save();
    }

    /**
     * Sync basic shipment information (from SyncShipmentInfo)
     */
    public function syncBasicShipmentInfo(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        $order = $shipment->purchaseOrder;

        if ($order->order_status === \App\Enums\OrderStatusEnum::Draft) {
            $order->updateQuietly([
                'order_status' => \App\Enums\OrderStatusEnum::Inprogress,
            ]);
        }

        // Update exchange rate based on Shipment Declaration Date
        if (!$shipment->is_exchange_rate_final) {
            $exchangeRate = $this->calculateExchangeRate($shipment);
            $shipment->updateQuietly([
                'exchange_rate' => $exchangeRate,
            ]);
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

    /**
     * Sync shipment lines information (from SyncShipmentLinesInfo)
     */
    public function syncShipmentLinesInfo(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        $order = $shipment->purchaseOrder;

        $shipment->purchaseShipmentLines()->update([
            'company_id' => $order->company_id,
            'currency' => $order->currency,
            'purchase_order_id' => $order->id,
        ]);

        $orderLinesData = $order->purchaseOrderLines()
            ->get(['id', 'product_id', 'unit_price', 'contract_price'])
            ->keyBy('product_id');

        $shipment->purchaseShipmentLines()
            ->each(function (PurchaseShipmentLine $line) use ($orderLinesData) {
                $orderLine = $orderLinesData->get($line->product_id);
                if ($orderLine) {
                    $line->updateQuietly([
                        'purchase_order_line_id' => $orderLine->id,
                        'unit_price' => $orderLine->unit_price,
                        'contract_price' => $orderLine->contract_price,
                    ]);
                }
            });
    }

    /**
     * Update shipment totals (from UpdateShipmentTotals)
     */
    public function updateShipmentTotals(int $shipmentId): void
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);

        $totalValue = $shipment->purchaseShipmentLines()->sum('value');
        $totalContractValue = $shipment->purchaseShipmentLines()->sum('contract_value');
        $totalExtraCost = collect($shipment->extra_costs)->sum() ?? 0;

        $totalQty = $shipment->purchaseShipmentLines()->sum('qty');
        $averageCost = null;
        if ($totalQty > 0) {
            $averageCost = $totalExtraCost / $totalQty;
        }

        $shipment->update([
            'total_value' => $totalValue,
            'total_contract_value' => $totalContractValue,
            'total_extra_cost' => $totalExtraCost,
            'average_cost' => $averageCost,
        ]);
    }

    /**
     * Mark shipment as delivered
     */
    public function markAsDelivered(int $shipmentId, array $data = []): bool
    {
        $shipment = PurchaseShipment::findOrFail($shipmentId);
        
        return $shipment->update(array_merge([
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Delivered ?? 'delivered',
            'delivered_date' => now(),
            'updated_by' => auth()->id(),
        ], $data));
    }

    /**
     * Calculate exchange rate for shipment
     */
    private function calculateExchangeRate(PurchaseShipment $shipment): ?float
    {
        if ($shipment->currency === 'VND') {
            return 1.0;
        }

        $exchangeRateService = app(ExchangeRateService::class);
        $date = null;

        if ($shipment->customs_clearance_date) {
            $date = $shipment->customs_clearance_date->format('Y-m-d');
        } elseif ($shipment->customs_declaration_date) {
            $date = $shipment->customs_declaration_date->format('Y-m-d');
        }

        if ($date) {
            return $exchangeRateService->getRate($shipment->currency, 'VND', $date);
        }

        return null;
    }

    /**
     * Create new shipment
     */
    public function create(array $data): PurchaseShipment
    {
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        $shipment = PurchaseShipment::create($data);
        $this->syncShipmentInfo($shipment->id);

        return $shipment;
    }

    /**
     * Update shipment
     */
    public function update(int $shipmentId, array $data): bool
    {
        if (auth()->check()) {
            $data['updated_by'] = auth()->id();
        }

        $result = PurchaseShipment::findOrFail($shipmentId)->update($data);
        
        if ($result) {
            $this->syncShipmentInfo($shipmentId);
        }

        return $result;
    }

    /**
     * Get shipments by purchase order
     */
    public function getByPurchaseOrder(int $purchaseOrderId): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseShipment::where('purchase_order_id', $purchaseOrderId)->get();
    }

    /**
     * Get shipments by status
     */
    public function getByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseShipment::where('shipment_status', $status)->get();
    }

    /**
     * Get pending shipments
     */
    public function getPendingShipments(): \Illuminate\Database\Eloquent\Collection
    {
        return PurchaseShipment::where('shipment_status', 'pending')->get();
    }

    /**
     * Calculate total shipment value for order
     */
    public function calculateTotalValueForOrder(int $purchaseOrderId): float
    {
        return PurchaseShipment::where('purchase_order_id', $purchaseOrderId)
            ->sum('total_value');
    }

    /**
     * Mark shipment as delivered (from MarkShipmentDelivered)
     */
    public function markShipmentDelivered(int $shipmentId): void
    {
        $shipment = $this->purchaseShipmentRepository->findById($shipmentId);
        if (!$shipment) {
            throw new \InvalidArgumentException("Shipment not found");
        }

        if (in_array($shipment->shipment_status, [
            \App\Enums\ShipmentStatusEnum::Cancelled,
            \App\Enums\ShipmentStatusEnum::Delivered,
        ])) {
            throw new \InvalidArgumentException("Cannot mark shipment as arrived. Current status: {$shipment->shipment_status->value}");
        }

        $this->purchaseShipmentRepository->update($shipmentId, [
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Delivered,
        ]);
    }
}