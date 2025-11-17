<?php

namespace Tests\Unit\SalesShipment;

use App\Services\SalesShipment\SalesShipmentService;
use Tests\TestCase;

/**
 * Test validation bug fix - không cần database
 */
class SalesShipmentValidationTest extends TestCase
{
    /** @test */
    public function test_validate_total_qty_per_inventory_line_should_fail()
    {
        // Mock service với method được override
        $service = new class extends SalesShipmentService {
            public function getInventoryTransactionRemaining(int $transactionId, ?int $excludeShipmentId = null): float
            {
                // Mock: Inventory line 1 chỉ có 500 available
                return 500;
            }
        };

        // Data giống user test: 2 transactions cùng inventory line, tổng 600 > 500
        $transactionsData = [
            [
                'schedule_line_id' => 1,
                'inventory_transaction_id' => 1,
                'qty' => 100,
                'exclude_shipment_id' => null,
            ],
            [
                'schedule_line_id' => 2,
                'inventory_transaction_id' => 1, // Same inventory line
                'qty' => 500, // Total = 600 > 500 available
                'exclude_shipment_id' => null,
            ]
        ];

        // Validate - phải có errors
        $errors = $service->validateTransactionsData($transactionsData);

        // Assert: Phải có lỗi validation
        $this->assertNotEmpty($errors, 'Validation phải báo lỗi khi tổng qty vượt quá available');
        
        // Kiểm tra message lỗi
        $errorKeys = array_keys($errors);
        $this->assertStringContainsString('qty', $errorKeys[0]);
        $this->assertStringContainsString('Tổng số lượng 600', $errors[$errorKeys[0]]);
        $this->assertStringContainsString('vượt quá tồn kho 500', $errors[$errorKeys[0]]);

        echo "\n=== VALIDATION BUG FIX TEST ===\n";
        echo "Transactions Data:\n";
        echo "- Transaction 1: inventory_id=1, qty=100\n";
        echo "- Transaction 2: inventory_id=1, qty=500\n";
        echo "- Total requested: 600, Available: 500\n";
        echo "Validation Result: " . (empty($errors) ? '❌ PASS (BUG)' : '✅ FAIL (FIXED)') . "\n";
        echo "Error Message: " . array_values($errors)[0] . "\n";
    }

    /** @test */
    public function test_validate_different_inventory_lines_should_pass()
    {
        // Mock service
        $service = new class extends SalesShipmentService {
            public function getInventoryTransactionRemaining(int $transactionId, ?int $excludeShipmentId = null): float
            {
                // Mock: Mỗi inventory line có 500 available
                return 500;
            }
        };

        // Data: 2 transactions khác inventory line, mỗi cái 300 < 500
        $transactionsData = [
            [
                'schedule_line_id' => 1,
                'inventory_transaction_id' => 1, // Line 1: 300 < 500 ✅
                'qty' => 300,
                'exclude_shipment_id' => null,
            ],
            [
                'schedule_line_id' => 2,
                'inventory_transaction_id' => 2, // Line 2: 300 < 500 ✅ 
                'qty' => 300,
                'exclude_shipment_id' => null,
            ]
        ];

        // Validate - không được có errors
        $errors = $service->validateTransactionsData($transactionsData);

        // Assert: Không có lỗi
        $this->assertEmpty($errors, 'Validation không được báo lỗi khi qty hợp lệ');

        echo "\n=== VALID CASE TEST ===\n";
        echo "Transactions Data:\n";
        echo "- Transaction 1: inventory_id=1, qty=300 (available=500) ✅\n";
        echo "- Transaction 2: inventory_id=2, qty=300 (available=500) ✅\n";
        echo "Validation Result: " . (empty($errors) ? '✅ PASS' : '❌ FAIL') . "\n";
    }
}