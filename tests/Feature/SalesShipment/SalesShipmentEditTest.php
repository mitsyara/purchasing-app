<?php

namespace Tests\Feature\SalesShipment;

use App\Models\Company;
use App\Models\Contact;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\SalesDeliverySchedule;
use App\Models\SalesDeliveryScheduleLine;
use App\Models\SalesShipment;
use App\Models\Warehouse;
use App\Services\SalesShipment\SalesShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test các tình huống edit SalesShipment với rollback logic
 */
class SalesShipmentEditTest extends TestCase
{
    use RefreshDatabase;

    private SalesShipmentService $service;
    private Company $company;
    private Warehouse $warehouse;
    private Contact $customer;
    private Product $product;
    private InventoryTransaction $parentLot;
    private SalesDeliverySchedule $schedule;
    private SalesDeliveryScheduleLine $scheduleLine;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(SalesShipmentService::class);
        
        // Tạo test data manually (không dùng factory)
        $this->company = Company::create([
            'company_name' => 'Test Company',
            'company_code' => 'TEST',
        ]);
        
        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'warehouse_name' => 'Test Warehouse',
            'warehouse_code' => 'WH001',
        ]);
        
        $this->customer = Contact::create([
            'contact_name' => 'Test Customer',
            'is_cus' => true,
        ]);
        
        $this->product = Product::create([
            'product_name' => 'Test Product',
            'product_code' => 'PROD001',
        ]);
        
        // Tạo parent lot với qty = 1000
        $this->parentLot = InventoryTransaction::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'transaction_direction' => \App\Enums\InventoryTransactionDirectionEnum::Import,
            'transaction_date' => now(),
            'qty' => 1000,
            'lot_no' => 'LOT001',
            'io_price' => 100,
            'sourceable_type' => 'ManualEntry', // Dummy source
            'sourceable_id' => 1,
            'io_currency' => 'VND',
        ]);
        
        // Tạo schedule và schedule line
        $this->schedule = SalesDeliverySchedule::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'schedule_code' => 'SCH001',
            'delivery_address' => 'Test Address',
        ]);
        
        $this->scheduleLine = SalesDeliveryScheduleLine::create([
            'sales_delivery_schedule_id' => $this->schedule->id,
            'product_id' => $this->product->id,
            'qty' => 800,
            'unit_price' => 100,
        ]);
    }

    /** @test */
    public function test_can_create_shipment_and_reduce_parent_lot_qty()
    {
        // Tạo shipment với qty = 500
        $shipment = SalesShipment::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Pending,
        ]);
        
        $shipment->deliverySchedules()->attach($this->schedule->id);
        
        $transactionsData = [
            [
                'schedule_line_id' => $this->scheduleLine->id,
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 500,
            ]
        ];
        
        // Sync transactions
        $this->service->syncShipmentTransactions($shipment, $transactionsData);
        
        // Kiểm tra child transaction được tạo
        $childTransactions = $shipment->fresh()->transactions;
        $this->assertCount(1, $childTransactions);
        $this->assertEquals(500, $childTransactions->first()->qty);
        $this->assertEquals($this->parentLot->id, $childTransactions->first()->parent_id);
        
        // Kiểm tra remaining của parent lot = 1000 - 500 = 500
        $remaining = $this->service->getInventoryTransactionRemaining($this->parentLot->id);
        $this->assertEquals(500, $remaining);
    }
    
    /** @test */
    public function test_can_edit_shipment_with_rollback_logic()
    {
        // Tạo shipment ban đầu với qty = 500
        $shipment = SalesShipment::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Pending,
        ]);
        
        $shipment->deliverySchedules()->attach($this->schedule->id);
        
        $transactionsData = [
            [
                'schedule_line_id' => $this->scheduleLine->id,
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 500,
            ]
        ];
        
        $this->service->syncShipmentTransactions($shipment, $transactionsData);
        
        // Kiểm tra remaining sau khi tạo = 500
        $remaining = $this->service->getInventoryTransactionRemaining($this->parentLot->id);
        $this->assertEquals(500, $remaining);
        
        // Edit: rollback logic - remaining phải = 1000 (exclude shipment đang edit)
        $remainingWhenEdit = $this->service->getInventoryTransactionRemaining($this->parentLot->id, $shipment->id);
        $this->assertEquals(1000, $remainingWhenEdit);
        
        // Edit shipment: thay đổi qty từ 500 → 800
        $newTransactionsData = [
            [
                'schedule_line_id' => $this->scheduleLine->id,
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 800,
            ]
        ];
        
        $this->service->syncShipmentTransactions($shipment, $newTransactionsData);
        
        // Kiểm tra child transaction mới
        $childTransactions = $shipment->fresh()->transactions;
        $this->assertCount(1, $childTransactions);
        $this->assertEquals(800, $childTransactions->first()->qty);
        
        // Kiểm tra remaining sau edit = 1000 - 800 = 200
        $remainingAfterEdit = $this->service->getInventoryTransactionRemaining($this->parentLot->id);
        $this->assertEquals(200, $remainingAfterEdit);
    }
    
    /** @test */
    public function test_form_options_include_lots_being_edited()
    {
        // Tạo shipment với qty = 1000 (hết toàn bộ lot)
        $shipment = SalesShipment::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Pending,
        ]);
        
        $shipment->deliverySchedules()->attach($this->schedule->id);
        
        $transactionsData = [
            [
                'schedule_line_id' => $this->scheduleLine->id,
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 1000, // Hết toàn bộ lot
            ]
        ];
        
        $this->service->syncShipmentTransactions($shipment, $transactionsData);
        
        // Kiểm tra remaining = 0
        $remaining = $this->service->getInventoryTransactionRemaining($this->parentLot->id);
        $this->assertEquals(0, $remaining);
        
        // Khi edit: form options phải bao gồm lot này (mặc dù remaining = 0)
        $options = $this->service->getFormOptionsForLotSelection(
            [$this->product->id], 
            $this->warehouse->id, 
            $shipment->id, // exclude shipment
            $this->parentLot->id // current transaction being edited
        );
        
        // Lot phải có trong options với remaining = 1000 (rollback)
        $this->assertArrayHasKey($this->parentLot->id, $options);
        $this->assertStringContainsString('1000', $options[$this->parentLot->id]);
    }
    
    /** @test */
    public function test_form_options_exclude_lots_with_zero_remaining()
    {
        // Tạo shipment khác sử dụng hết lot
        $otherShipment = SalesShipment::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Pending,
        ]);
        
        $otherSchedule = SalesDeliverySchedule::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'schedule_code' => 'SCH002',
            'delivery_address' => 'Test Address 2',
        ]);
        
        $otherScheduleLine = SalesDeliveryScheduleLine::create([
            'sales_delivery_schedule_id' => $otherSchedule->id,
            'product_id' => $this->product->id,
            'qty' => 1000,
            'unit_price' => 100,
        ]);
        
        $otherShipment->deliverySchedules()->attach($otherSchedule->id);
        
        $transactionsData = [
            [
                'schedule_line_id' => $otherScheduleLine->id,
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 1000, // Hết toàn bộ lot
            ]
        ];
        
        $this->service->syncShipmentTransactions($otherShipment, $transactionsData);
        
        // Khi tạo shipment mới: form options không được bao gồm lot này
        $options = $this->service->getFormOptionsForLotSelection(
            [$this->product->id], 
            $this->warehouse->id
        );
        
        // Lot không có trong options vì remaining = 0
        $this->assertArrayNotHasKey($this->parentLot->id, $options);
    }
    
    /** @test */
    public function test_can_change_schedule_line_and_maintain_lot_options()
    {
        // Tạo schedule line khác
        $scheduleLineB = SalesDeliveryScheduleLine::create([
            'sales_delivery_schedule_id' => $this->schedule->id,
            'product_id' => $this->product->id,
            'qty' => 600,
            'unit_price' => 100,
        ]);
        
        // Tạo shipment với schedule line A
        $shipment = SalesShipment::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'shipment_status' => \App\Enums\ShipmentStatusEnum::Pending,
        ]);
        
        $shipment->deliverySchedules()->attach($this->schedule->id);
        
        $transactionsData = [
            [
                'schedule_line_id' => $this->scheduleLine->id, // Schedule line A
                'inventory_transaction_id' => $this->parentLot->id,
                'qty' => 1000, // Hết toàn bộ lot
            ]
        ];
        
        $this->service->syncShipmentTransactions($shipment, $transactionsData);
        
        // Khi edit và đổi sang schedule line B: lot vẫn phải hiển thị
        $options = $this->service->getFormOptionsForLotSelection(
            [$this->product->id], 
            $this->warehouse->id, 
            $shipment->id, // exclude current shipment
            $this->parentLot->id // lot đang được chọn
        );
        
        // Lot phải có trong options với remaining = 1000 (rollback)
        $this->assertArrayHasKey($this->parentLot->id, $options);
        $this->assertStringContainsString('1000', $options[$this->parentLot->id]);
    }
}