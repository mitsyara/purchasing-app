<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Unit::withoutEvents(function (): void {
            $units = [
                ['KG', 'Kilogram'],
                ['G', 'Gram'],
                ['MT', 'Metric Ton'],
                ['L', 'Litre'],
                ['ML', 'Mili Litre'],
                ['BOU', 'Bou'],
            ];
            foreach ($units as $unit) {
                \App\Models\Unit::create([
                    'unit_code' => $unit[0],
                    'unit_name' => $unit[1],
                ]);
            }

            $this->command->info('Units table seeded!');
        });
    }
}
