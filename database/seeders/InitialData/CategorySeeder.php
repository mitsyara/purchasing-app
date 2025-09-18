<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Category::withoutEvents(function (): void {
            $vat_0 = \App\Models\Vat::query()->where('vat_name', 'V-0')->first()?->id;
            $vat_5 = \App\Models\Vat::query()->where('vat_name', 'V-5')->first()?->id;
            $vat_8 = \App\Models\Vat::query()->where('vat_name', 'V-8')->first()?->id;
            $vat_10 = \App\Models\Vat::query()->where('vat_name', 'V-10')->first()?->id;

            $main_categories = [
                ['VETT', 'Nguyên liệu thuốc Thú Y', $vat_5],
                ['DRUG', 'Nguyên liệu Dược phẩm', $vat_5],
                ['CHEM', 'Nguyên liệu Hoá chất', $vat_10],
                ['FEED', 'Thức ăn Chăn nuôi', $vat_0],
                ['BACT', 'Vi khuẩn - Vi sinh', $vat_5],
                ['ENZE', 'Nguyên liệu Men - Enzyme', $vat_5],
                ['FOOD', 'Nguyên liệu Thực phẩm', $vat_10],
                ['EXTR', 'Nguyên liệu Chiết xuất', $vat_10],
            ];
            foreach ($main_categories as $category) {
                \App\Models\Category::create([
                    'category_code' => $category[0],
                    'category_name' => $category[1],
                    'vat_id' => $category[2],
                    'is_gmp_required' => in_array($category[0], ['VETT', 'DRUG']),
                ]);
            }

            $this->command->info('Categories table seeded!');
        });
    }
}
