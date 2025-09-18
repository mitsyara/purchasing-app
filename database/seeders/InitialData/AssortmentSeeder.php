<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssortmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Assortment::withoutEvents(function () {
            $categories = \App\Models\Category::pluck('id', 'category_code');

            $amoxs = \App\Models\Product::with(['mfg.country'])
                ->whereHas('mfg.country', fn($q) => $q->where('alpha3', 'CHN'))
                ->where('product_name', 'like', '%amoxicillin trihydrate%')
                ->pluck('id')->toArray();

            $flors = \App\Models\Product::with(['mfg.country'])
                ->whereHas('mfg.country', fn($q) => $q->where('alpha3', 'CHN'))
                ->where('product_name', 'like', '%florfenicol%')
                ->pluck('id')->toArray();

            // Amoxicillin China
            \App\Models\Assortment::create([
                'assortment_code' => 'Amox-CN',
                'assortment_name' => 'Amoxicillin (CHINA)',
                'category_id' => $categories['VETT'],
            ])
                ->products()->attach($amoxs);

            // Florfenicol China
            \App\Models\Assortment::create([
                'assortment_code' => 'Flor-CN',
                'assortment_name' => 'Florfenicol (CHINA)',
                'category_id' => $categories['VETT'],
            ])
                ->products()->attach($flors);
        });
    }
}
