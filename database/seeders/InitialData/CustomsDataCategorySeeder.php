<?php

namespace Database\Seeders\InitialData;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomsDataCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Category::withoutEvents(function (): void {
            $categories = [
                "Albendazole",
                "Amoxicillin",
                "Ampicillin",
                "Amprolium",
                "Analgin",
                "Apramycin",
                "Atropine",
                "Antipyrine",
                "Aspirin",
                "Amitraz",
                "Azithromycin",
                "Benzalkonium Chloride",
                "Berberin",
                "Bromhexin",
                "Bacitracin",
                "Benzylpenicillin",
                "Butaphosphan",
                "Cefalexin",
                "Cefixime",
                "Calcium Gluconate",
                "Carbaspirin",
                "Cefadroxil",
                "Cefotaxime",
                "Cefquinome",
                "Ceftiofur",
                "Colistin",
                "Ceftriaxone",
                "Cetrimide",
                "Chloroxylenol",
                "Clorsulon",
                "Cloprostenol",
                "Cyanocobalamin",
                "Dexamethasone",
                "Diclofenac",
                "Doxycycline",
                "Diaveridine",
                "Diclazuril",
                "Doramectin",
                "Enrofloxacin",
                "Erythromycin",
                "Flunixin",
                "Fosfomycin",
                "Fenbendazole",
                "Fluconazole",
                "Gentamycin",
                "Glutaraldehyde",
                "Halquinol",
                "Itraconazole",
                "Iron Dextran",
                "Ivermectin",
                "Kanamycin Mono Sulfate",
                "Ketoprofen",
                "Levamisole",
                "Lincomycin",
                "Lindocaine",
                "Marbofloxacin",
                "Miconazole",
                "Dimethylacetamide",
                "Pyrrolidone",
                "Neomycin",
                "Nitroxynil",
                "Norfloxacin",
                "Nystatin",
                "Oxytetracycline",
                "Oxolinic",
                "Oxytocin",
                "Paracetamol",
                "Piperazine",
                "Praziquantel",
                "Prednisolone",
                "Propylene Glycol",
                "Phenylbutazone",
                "Progesterone",
                "Rifampicin",
                "Spectinomycin",
                "Spiramycin",
                "Sulbactam",
                "Streptomycin",
                "Sulfachloropyrazine",
                "Sulfachloropyridazine",
                "Sulfadiazine",
                "Sulfadimethoxine",
                "Sulfadimidine",
                "Sulfadoxine",
                "Sulfaguanidine",
                "Sulfamethoxazole",
                "Sulfamethoxypyridazine",
                "Sulfamonomethoxin",
                "Sulfaquinoxaline",
                "Thiamphenicol",
                "Tiamulin",
                "Tilmicosin",
                "Tokulsil",
                "Toltrazuril",
                "Trimethoprim",
                "Tulathromycin",
                "Tylosin",
                "Tylvalosin",
                "Triclabendazole",
                "Florfenicol",
            ];

            $now = now();

            $data = array_map(fn($category_name) => [
                'name' => $category_name,
                'created_at' => $now,
                'updated_at' => $now,
            ], $categories);

            // Insert data
            \App\Models\CustomsDataCategory::insert($data);

            // Run Cache
            \Illuminate\Support\Facades\Cache::rememberForever(
                'customs_data_categories.all',
                function (): \Illuminate\Database\Eloquent\Collection {
                    return \App\Models\CustomsDataCategory::all(['id', 'name', 'keywords']);
                }
            );

            $this->command->info('Customs Data Categories table seeded!');
        });
    }
}
