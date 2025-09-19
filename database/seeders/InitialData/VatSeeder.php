<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Vat::withoutEvents(function (): void {
            // code, value, notes
            $vats = [
                ['V-0', 0, 'VAT 0%'],
                ['V-5', 5, 'VAT 5%'],
                ['V-8', 8, 'VAT 8%'],
                ['V-10', 10, 'VAT 10%'],
            ];
            foreach ($vats as $vat) {
                \App\Models\Vat::create([
                    'vat_name' => $vat[0],
                    'vat_value' => $vat[1],
                    'notes' => $vat[2],
                ]);
            }
            $this->command->info('Vats table seeded!');
        });
    }
}
