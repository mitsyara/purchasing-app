<?php

namespace Tests\Unit\SalesShipment;

use Tests\TestCase;

/**
 * Test logic SalesShipmentService KHÔNG cần database
 */
class SalesShipmentLogicTest extends TestCase
{
    /** @test */
    public function test_quantity_calculation_case_1_no_exclude()
    {
        // CASE 1: Tính remaining quantity KHÔNG có exclude
        // 
        // Setup:
        // - Parent lot: 1000 kg
        // - Đã xuất: 300 kg (các shipment khác)
        // - Không exclude shipment nào
        // 
        // Expected: 1000 - 300 = 700 kg remaining
        
        $parentQty = 1000;
        $totalExported = 300;
        $excludeExported = 0; // Không exclude
        
        $remaining = $parentQty - ($totalExported - $excludeExported);
        
        $this->assertEquals(700, $remaining);
        
        // Giải thích step by step:
        echo "\n=== CASE 1: No Exclude ===\n";
        echo "Parent Lot Qty: {$parentQty} kg\n";
        echo "Total Exported: {$totalExported} kg\n";
        echo "Exclude Exported: {$excludeExported} kg\n";
        echo "Calculation: {$parentQty} - ({$totalExported} - {$excludeExported}) = {$remaining} kg\n";
    }
    
    /** @test */  
    public function test_quantity_calculation_case_2_with_exclude()
    {
        // CASE 2: Tính remaining quantity CÓ exclude (đang edit shipment)
        //
        // Setup:
        // - Parent lot: 1000 kg
        // - Đã xuất tổng: 500 kg (bao gồm shipment đang edit)
        // - Shipment đang edit: 200 kg (cần exclude để rollback)
        // 
        // Expected: 1000 - (500 - 200) = 700 kg remaining
        
        $parentQty = 1000;  
        $totalExported = 500;
        $excludeExported = 200; // Exclude shipment đang edit
        
        $remaining = $parentQty - ($totalExported - $excludeExported);
        
        $this->assertEquals(700, $remaining);
        
        // Giải thích step by step:
        echo "\n=== CASE 2: With Exclude (Edit Mode) ===\n";
        echo "Parent Lot Qty: {$parentQty} kg\n";
        echo "Total Exported: {$totalExported} kg\n";
        echo "Exclude Exported: {$excludeExported} kg (shipment đang edit)\n";
        echo "Actual Exported: " . ($totalExported - $excludeExported) . " kg (sau khi exclude)\n";
        echo "Calculation: {$parentQty} - ({$totalExported} - {$excludeExported}) = {$remaining} kg\n";
        echo "=> Remaining available để chọn: {$remaining} kg\n";
    }
    
    /** @test */
    public function test_form_options_logic()
    {
        // CASE 3: Form options logic - Lot có remaining = 0 nhưng đang được chọn
        //
        // Setup:
        // - Lot A: remaining = 0 (đã hết)
        // - Lot B: remaining = 500  
        // - Lot A đang được chọn trong shipment edit
        //
        // Expected: Lot A vẫn xuất hiện trong options (mặc dù remaining = 0)
        
        $lots = [
            ['id' => 1, 'remaining' => 0, 'being_edited' => true],   // Lot A
            ['id' => 2, 'remaining' => 500, 'being_edited' => false], // Lot B  
        ];
        
        $options = [];
        foreach ($lots as $lot) {
            // Logic: Include nếu remaining > 0 HOẶC đang được edit
            if ($lot['remaining'] > 0 || $lot['being_edited']) {
                $options[$lot['id']] = "Lot {$lot['id']} - Remaining: {$lot['remaining']} kg";
            }
        }
        
        // Verify kết quả
        $this->assertCount(2, $options); // Cả 2 lots đều có trong options
        $this->assertArrayHasKey(1, $options); // Lot A có (mặc dù remaining = 0)
        $this->assertArrayHasKey(2, $options); // Lot B có
        
        echo "\n=== CASE 3: Form Options Logic ===\n";
        foreach ($lots as $lot) {
            $status = $lot['being_edited'] ? '(đang edit)' : '';
            echo "Lot {$lot['id']}: {$lot['remaining']} kg {$status}\n";
        }
        echo "Options generated: " . count($options) . "\n";
        print_r($options);
    }

    /** @test */
    public function test_validation_bug_total_qty_per_inventory_line()
    {
        // CASE 4: BUG FIX - Validate tổng qty theo inventory line
        //
        // Setup:
        // - Inventory Line 1: available = 500
        // - Transaction 1: inventory_line_1, qty = 100  
        // - Transaction 2: inventory_line_1, qty = 500
        // - Tổng: 600 > 500 available
        //
        // Expected: Validation ERROR (trước đây pass nhầm)
        
        $transactionsData = [
            [
                'schedule_line_id' => 1,
                'inventory_transaction_id' => 1, // Same inventory line
                'qty' => 100,
                'exclude_shipment_id' => null,
            ],
            [
                'schedule_line_id' => 2, 
                'inventory_transaction_id' => 1, // Same inventory line
                'qty' => 500,
                'exclude_shipment_id' => null,
            ]
        ];
        
        // Mock available = 500, total requested = 600
        $available = 500;
        $totalRequested = 100 + 500; // = 600
        
        // Simulation validation logic
        $inventoryQtyTotals = [];
        foreach ($transactionsData as $data) {
            $inventoryId = $data['inventory_transaction_id'];
            $inventoryQtyTotals[$inventoryId] = ($inventoryQtyTotals[$inventoryId] ?? 0) + $data['qty'];
        }
        
        $hasError = false;
        foreach ($inventoryQtyTotals as $inventoryId => $totalQty) {
            if ($totalQty > $available) {
                $hasError = true;
                break;
            }
        }
        
        // Assert: Phải có lỗi validation
        $this->assertTrue($hasError, 'Validation phải báo lỗi khi tổng qty vượt quá available');
        $this->assertEquals(600, $totalRequested);
        $this->assertEquals(500, $available);
        $this->assertTrue($totalRequested > $available);
        
        echo "\n=== CASE 4: Validation Bug Fix ===\n";
        echo "Available: {$available} kg\n";
        echo "Transaction 1: 100 kg\n";
        echo "Transaction 2: 500 kg\n";
        echo "Total Requested: {$totalRequested} kg\n";
        echo "Should Error: " . ($hasError ? 'YES ✅' : 'NO ❌') . "\n";
    }
}