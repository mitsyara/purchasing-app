<?php

namespace Database\Seeders\FakeData;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class PurchaseOrderSeeder extends Seeder
{
    public array $suppliers;
    public array $users;
    public array $products;

    public function __construct()
    {
        $this->suppliers = \App\Models\Contact::where('is_trader', true)
            // ->whereHas('country', fn($sq) => $sq->whereNot('alpha3', 'VNM'))
            ->pluck('id')->toArray();
        $this->users = \App\Models\User::pluck('id')->toArray();
        $this->products = \App\Models\Product::where('is_active', true)->pluck('id')->toArray();
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding: PurchaseOrder');
        for ($i = 0; $i < rand(2, 5); $i++) {
            $orderData = $this->randomOrderData($i);
            $order = \App\Models\PurchaseOrder::create($orderData);
            $orderLines = [];
            for ($j = 0; $j < rand(1, 5); $j++) {
                $orderLines[] = $this->randomOrderLine();
            }
            $order->purchaseOrderLines()->createMany($orderLines);
            $order->syncOrderInfo();
        }
    }

    public function randomOrderData(int $i): array
    {
        return [
            'order_status' => \App\Enums\OrderStatusEnum::Draft,
            'order_date' => $this->randomDateBetween('2025-05-01', '2025-09-22'),
            'order_number' => 'PO-TEST-000' . ($i + 1),

            // buyer
            'company_id' => \App\Models\Company::inRandomOrder()->first()->id,
            // supplier
            'supplier_id' => Arr::random($this->suppliers),
            // contract supplier
            'supplier_contract_id' => Arr::random($this->suppliers),
            // money receiver
            'supplier_payment_id' => Arr::random($this->suppliers),

            'import_warehouse_id' => \App\Models\Warehouse::inRandomOrder()->first()->id,
            'import_port_id' => \App\Models\Port::inRandomOrder()->first()->id,

            'staff_buy_id' => Arr::random($this->users),
            'staff_approved_id'  => Arr::random($this->users),
            'staff_sales_id' => Arr::random($this->users),

            'staff_docs_id' => Arr::random($this->users),
            'staff_declarant_id' => Arr::random($this->users),
            'staff_declarant_processing_id' => Arr::random($this->users),

            'incoterm' => Arr::random(\App\Enums\IncotermEnum::allCases()),
            'currency' => 'USD',

            'pay_term_delay_at' => \App\Enums\PaytermDelayAtEnum::OrderDate,
            'pay_term_days' => Arr::random([15, 30, 45, 60, 90]),

            'notes' => 'This is a sample purchase order created for testing purposes.',

            'created_by' => Arr::random($this->users),
            'updated_by' => Arr::random($this->users),
        ];
    }

    public function randomOrderLine(): array
    {
        return [
            'product_id' => Arr::random($this->products),
            'qty' => rand(1, 10) * 100,
            'unit_price' => rand(5, 10) * 1.25,
            'contract_price' => Arr::random([rand(5, 10) * 1.25, null]),
        ];
    }

    public function randomDateBetween(string $startDate, string $endDate): string|\Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startDate)->timestamp;
        $end   = \Carbon\Carbon::parse($endDate)->timestamp;
        $randomTimestamp = rand($start, $end);
        return \Carbon\Carbon::createFromTimestamp($randomTimestamp);
    }
}
