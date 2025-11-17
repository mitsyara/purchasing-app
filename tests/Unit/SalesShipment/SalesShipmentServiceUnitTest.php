<?php

namespace Tests\Unit\SalesShipment;

use App\Services\SalesShipment\SalesShipmentService;
use Tests\TestCase;

/**
 * Unit test đơn giản cho SalesShipmentService
 */
class SalesShipmentServiceUnitTest extends TestCase
{
    /** @test */
    public function test_service_can_be_instantiated()
    {
        $service = new SalesShipmentService();
        
        $this->assertInstanceOf(SalesShipmentService::class, $service);
    }

    /** @test */
    public function test_service_has_required_methods()
    {
        $service = new SalesShipmentService();
        
        // Kiểm tra các method quan trọng tồn tại
        $this->assertTrue(method_exists($service, 'getFormOptionsForLotSelection'));
        $this->assertTrue(method_exists($service, 'getShipmentTransactionIds'));
        $this->assertTrue(method_exists($service, 'getInventoryTransactionRemaining'));
        $this->assertTrue(method_exists($service, 'syncShipmentTransactions'));
    }

    /** @test */
    public function test_get_form_options_handles_empty_input()
    {
        $service = new SalesShipmentService();
        
        // Test với empty product IDs - không crash
        $options = $service->getFormOptionsForLotSelection([], 1);
        
        $this->assertIsArray($options);
    }

    /** @test */
    public function test_get_shipment_transaction_ids_handles_non_existent_shipment()
    {
        $service = new SalesShipmentService();
        
        // Test với shipment không tồn tại - trả về empty array
        $result = $service->getShipmentTransactionIds(99999);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}