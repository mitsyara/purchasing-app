<?php

namespace App\Filament\Resources\SalesShipments\Helpers;

use App\Models\SalesDeliverySchedule;
use App\Models\SalesShipment;
use App\Services\SalesShipment\SalesShipmentService;
use Filament\Actions as A;

/**
 * Helper class cho SalesShipmentResource
 * Tách logic phức tạp ra khỏi Resource chính
 */
class SalesShipmentResourceHelper
{
    public function __construct(
        private SalesShipmentService $service
    ) {}

    /**
     * Load form data cho edit action
     */
    public function loadFormData(SalesShipment $record): array
    {
        $data = $record->toArray();
        // thêm mapping pivot cho transactions repeater
        $data['transactions'] = $this->service->loadTransactionsData($record);
        return $data;
    }

    /**
     * Sync data sau khi save - xử lý transactions
     */
    public function syncData(A\Action $action, array $data): array
    {
        $transactionsData = $data['transactions'] ?? [];

        // Clean data
        unset($data['transactions'], $data['id']);

        // Process transactions after save
        $action->after(function (SalesShipment $record) use ($transactionsData) {
            if (!empty($transactionsData)) {
                $this->processTransactions($record, $transactionsData);
            }
        });

        return $data;
    }

    /**
     * Tự động tính qty cho form
     */
    public function calculateOptimalQty(callable $get): ?float
    {
        $transactionId = $get('inventory_transaction_id');
        $scheduleLineId = $get('schedule_line_id');
        $shipmentId = $get('../../id');

        if (!$transactionId || !$scheduleLineId) {
            return null;
        }

        return $this->service->calculateOptimalQty($transactionId, $scheduleLineId, $shipmentId);
    }

    /**
     * Lấy options cho lot trong form
     */
    public function getLotOptions(callable $get): array
    {
        $shipmentId = $get('../../id');
        $warehouseId = $get('../../warehouse_id');
        $scheduleLineId = $get('schedule_line_id');
        $currentTransactionId = $get('inventory_transaction_id');

        if (!$scheduleLineId || !$warehouseId) {
            return [];
        }

        $productIds = $this->service->getProductIdsFromScheduleLine($scheduleLineId);

        return $this->service->getFormOptionsForLotSelection(
            $productIds,
            $warehouseId,
            $shipmentId,
            $currentTransactionId
        );
    }

    /**
     * Lấy label cho lot hiển thị
     */
    public function getLotLabel($value, callable $get): ?string
    {
        if (!$value) return null;

        $shipmentId = $get('../../id');
        $transaction = \App\Models\InventoryTransaction::find($value);

        if (!$transaction) return null;

        $remaining = $this->service->getInventoryTransactionRemaining($value, $shipmentId);
        return $transaction->lot_description . ' | Còn: ' . __number_string_converter($remaining);
    }

    /**
     * Check address conflicts in selected schedules
     */
    public function checkAddressConflicts(array $scheduleIds): void
    {
        if (count($scheduleIds) <= 1) return;

        $addresses = SalesDeliverySchedule::query()
            ->whereIn('id', $scheduleIds)
            ->pluck('delivery_address');

        if ($addresses->some(fn($addr) => $addr !== $addresses->first())) {
            \Filament\Notifications\Notification::make()
                ->title(__('Selected schedules have different delivery addresses'))
                ->warning()
                ->send();
        }
    }

    /**
     * Validate unique schedule_line_id + inventory_transaction_id pair
     */
    public function validateUniqueScheduleLotPair(callable $get, \Filament\Forms\Components\Field $component): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($get, $component) {
            $scheduleLineId = $get('schedule_line_id');
            if (!$scheduleLineId || !$value) {
                return;
            }

            $currentKey = $component->getStatePath();
            $currentTransactions = $component->getParentRepeater()->getState();

            // Extract UUID từ statePath để tránh check chính nó
            $pathParts = explode('.', $currentKey);
            $currentItemUuid = $pathParts[count($pathParts) - 2];

            // Kiểm tra trùng lặp cặp schedule_line_id + inventory_transaction_id
            foreach ($currentTransactions as $uuid => $transaction) {
                // Bỏ qua chính item đang validate
                if ($uuid === $currentItemUuid) {
                    continue;
                }

                // Check trùng cặp
                if (($transaction['schedule_line_id'] ?? null) == $scheduleLineId &&
                    ($transaction['inventory_transaction_id'] ?? null) == $value
                ) {
                    $fail('Trùng lặp kế hoạch');
                    return;
                }
            }
        };
    }

    /**
     * Validate transaction qty field
     */
    public function validateTransactionQty(callable $get, \Filament\Forms\Components\Field $component): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($get, $component) {
            if (!$get('inventory_transaction_id') || !$get('schedule_line_id') || !$value) {
                return;
            }

            $currentKey = $component->getStatePath();
            $currentTransactions = $component->getParentRepeater()->getState();

            // Extract UUID từ statePath: "mountedActions.0.data.transactions.UUID.qty"
            $pathParts = explode('.', $currentKey);
            $currentItemUuid = $pathParts[count($pathParts) - 2]; // Lấy phần tử thứ 2 từ cuối

            $shipmentId = $get('../../id');
            $inventoryTransactionId = $get('inventory_transaction_id');
            $scheduleLineId = $get('schedule_line_id');

            // 1. VALIDATE LOT REMAINING
            // Tính tổng qty của cùng inventory_transaction_id đến trước item hiện tại
            $totalQtyBeforeForLot = 0;
            foreach ($currentTransactions as $uuid => $transaction) {
                // Dừng lại khi gặp item hiện tại
                if ($uuid === $currentItemUuid) {
                    break;
                }

                // Cộng qty nếu cùng inventory_transaction_id
                if (($transaction['inventory_transaction_id'] ?? null) == $inventoryTransactionId) {
                    $totalQtyBeforeForLot += floatval($transaction['qty'] ?? 0);
                }
            }

            // Lấy remaining của lot
            $lotRemaining = $this->service->getInventoryTransactionRemaining($inventoryTransactionId, $shipmentId);

            // Check tổng qty (đã dùng + đang nhập) có vượt lot remaining không
            $totalAfterCurrentInputForLot = $totalQtyBeforeForLot + $value;
            if ($totalAfterCurrentInputForLot > $lotRemaining) {
                $availableForThisLot = $lotRemaining - $totalQtyBeforeForLot;
                $fail("Lot còn {$availableForThisLot}");
                return;
            }

            // 2. VALIDATE SCHEDULE LINE REMAINING
            // Tính tổng qty của cùng schedule_line_id trong toàn bộ form (bao gồm item hiện tại)
            $totalQtyForScheduleLine = 0;
            foreach ($currentTransactions as $uuid => $transaction) {
                if (($transaction['schedule_line_id'] ?? null) == $scheduleLineId) {
                    if ($uuid === $currentItemUuid) {
                        // Dùng value mới cho item hiện tại
                        $totalQtyForScheduleLine += floatval($value);
                    } else {
                        // Dùng qty cũ cho các item khác
                        $totalQtyForScheduleLine += floatval($transaction['qty'] ?? 0);
                    }
                }
            }

            // Lấy remaining của schedule line
            $scheduleRemaining = $this->service->getScheduleLineRemaining($scheduleLineId, $shipmentId);

            // Check tổng qty có vượt schedule remaining không
            if ($totalQtyForScheduleLine > $scheduleRemaining) {
                $currentScheduleQty = $totalQtyForScheduleLine - $value; // Qty đã có của schedule line
                $fail("Kế hoạch đã có {$currentScheduleQty}");
                return;
            }
        };
    }

    /**
     * Process transactions with validation
     */
    private function processTransactions(SalesShipment $record, array $transactionsData): void
    {
        // Add shipment ID for validation context
        foreach ($transactionsData as &$transactionData) {
            $transactionData['exclude_shipment_id'] = $record->id;
        }

        // Validate
        $errors = $this->service->validateTransactionsData($transactionsData);
        if (!empty($errors)) {
            $validator = validator([], []);
            $messageBag = $validator->errors();

            // Add errors to message bag
            foreach ($errors as $field => $message) {
                $messageBag->add($field, $message);
            }

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Sync
        $this->service->syncShipmentTransactions($record, $transactionsData);
    }
}
