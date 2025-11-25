<?php

namespace Tests\Unit\Services\InventoryAdjustment;

use Tests\TestCase;
use App\Services\InventoryAdjustment\InventoryAdjustmentService;
use App\Models\InventoryAdjustment;
use App\Models\Company;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryAdjustmentService $service;
    protected Company $company;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(InventoryAdjustmentService::class);
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function it_can_create_adjustment_with_in_lines()
    {
        $data = [
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'adjustment_status' => \App\Enums\OrderStatusEnum::Draft,
            'adjustment_date' => today(),
            'reason' => 'Test adjustment',
            'lines_in' => [
                [
                    'product_id' => $this->product->id,
                    'lots' => [
                        [
                            'lot_no' => 'LOT001',
                            'mfg_date' => '2024-01-01',
                            'exp_date' => '2025-01-01',
                            'adjustment_qty' => 100,
                            'io_price' => 10000,
                        ]
                    ]
                ]
            ],
            'lines_out' => []
        ];

        $adjustment = $this->service->createOrUpdate($data);

        $this->assertInstanceOf(InventoryAdjustment::class, $adjustment);
        $this->assertEquals(1, $adjustment->adjustmentsLines()->count());
        $this->assertEquals(100, $adjustment->adjustmentsLines()->first()->adjustment_qty);
    }

    /** @test */
    public function it_can_load_form_data_correctly()
    {
        $adjustment = InventoryAdjustment::factory()
            ->for($this->company)
            ->for($this->warehouse)
            ->create();

        // Create IN line
        $adjustment->adjustmentsLines()->create([
            'product_id' => $this->product->id,
            'lot_no' => 'LOT001',
            'adjustment_qty' => 50,
            'io_price' => 5000,
        ]);

        // Create OUT line
        $adjustment->adjustmentsLines()->create([
            'product_id' => $this->product->id,
            'lot_no' => 'LOT002',
            'adjustment_qty' => -30,
            'io_price' => 7000,
            'parent_transaction_id' => 1,
        ]);

        $formData = $this->service->loadFormData($adjustment);

        $this->assertArrayHasKey('lines_in', $formData);
        $this->assertArrayHasKey('lines_out', $formData);
        $this->assertCount(1, $formData['lines_in']);
        $this->assertCount(1, $formData['lines_out']);
        
        // Check IN data
        $this->assertEquals(50, $formData['lines_in'][0]['lots'][0]['adjustment_qty']);
        
        // Check OUT data (should be positive in form)
        $this->assertEquals(30, $formData['lines_out'][0]['lots'][0]['adjustment_qty']);
        $this->assertEquals(1, $formData['lines_out'][0]['lots'][0]['parent_transaction_id']);
    }
}