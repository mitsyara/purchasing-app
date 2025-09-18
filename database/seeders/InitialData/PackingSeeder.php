<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Packing::withoutEvents(function (): void {
            $packs = ['thùng', 'bao', 'túi', 'chai', 'lon', 'lọ', 'can'];
            $numbers = [1, 2, 3, 5, 10, 20, 25, 30, 50, 100, 200, 300, 500];
            $units = [
                'kg' => \App\Models\Unit::query()->where('unit_code', 'KG')->first()?->id,
                'g' => \App\Models\Unit::query()->where('unit_code', 'G')->first()?->id,
                'l' => \App\Models\Unit::query()->where('unit_code', 'L')->first()?->id,
                'ml' => \App\Models\Unit::query()->where('unit_code', 'ML')->first()?->id,
                'bou' => \App\Models\Unit::query()->where('unit_code', 'BOU')->first()?->id,
            ];

            // Normal packings
            foreach ($packs as $pack) {
                foreach ($units as $unit => $unit_id) {
                    foreach ($numbers as $num) {
                        \App\Models\Packing::create([
                            'packing_name' => $num . $unit . '/' . $pack,
                            'unit_conversion_value' => $num,
                            'unit_id' => $unit_id,
                        ]);
                    }
                }
            }

            // Abnormal packings
            \App\Models\Packing::withoutEvents(function () use ($units) {
                \App\Models\Packing::create([
                    'packing_name' => '2.7kg/thùng',
                    'unit_conversion_value' => 2.7,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '4kg/thùng',
                    'unit_conversion_value' => 4,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '5.5kg/thùng',
                    'unit_conversion_value' => 5.5,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '6kg/thùng',
                    'unit_conversion_value' => 6,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '8kg/thùng',
                    'unit_conversion_value' => 8,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '12.5kg/thùng',
                    'unit_conversion_value' => 12.5,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '15kg/thùng',
                    'unit_conversion_value' => 15,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '15.4kg/thùng',
                    'unit_conversion_value' => 15.4,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '24kg/thùng',
                    'unit_conversion_value' => 24,
                    'unit_id' => $units['kg'],
                ]);
                // Thùng ĐVT khác
                \App\Models\Packing::create([
                    'packing_name' => '1350g/thùng',
                    'unit_conversion_value' => 1350,
                    'unit_id' => $units['g'],
                ]);
                // Phuy
                \App\Models\Packing::create([
                    'packing_name' => '200kg/phuy',
                    'unit_conversion_value' => 200,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '215kg/phuy',
                    'unit_conversion_value' => 215,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '220kg/phuy',
                    'unit_conversion_value' => 220,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '230kg/phuy',
                    'unit_conversion_value' => 230,
                    'unit_id' => $units['kg'],
                ]);
                \App\Models\Packing::create([
                    'packing_name' => '210l/phuy',
                    'unit_conversion_value' => 210,
                    'unit_id' => $units['l'],
                ]);
            });

            $this->command->info('Packings table seeded!');
        });
    }
}
