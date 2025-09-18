<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Warehouse::withoutEvents(function () {
            // $manager_id = \App\Models\User::where('email', 'anh.tl@vhl.com.vn')->first()?->id;
            $warehouses = [
                'WH-HY' => [
                    'warehouse_name' => 'Kho Hưng Yên',
                    'warehouse_address' => 'X2H6+39R, TT. Như Quỳnh, Văn Lâm, Hưng Yên',
                    'region' => \App\Enums\RegionEnum::North->value,
                ],
                'WH-BD' => [
                    'warehouse_name' => 'Kho Bình Dương',
                    'warehouse_address' => 'Đại lộ Độc Lập, KCN VSIP, TP.Thuận An, Bình Dương',
                    'region' => \App\Enums\RegionEnum::South->value,
                ]
            ];
            foreach ($warehouses as $code => $info) {
                \App\Models\Warehouse::create([
                    'warehouse_code' => $code,
                    ...$info,
                ]);
            }
            $this->command->info('Warehouses table seeded!');
        });
    }
}
