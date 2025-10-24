<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $initial = true;
        $force = false;

        // Real data
        if ($initial) {
            $seeders = [
                InitialData\UserSeeder::class,
                InitialData\CountrySeeder::class,
                InitialData\CompanySeeder::class,
                InitialData\VatSeeder::class,
                InitialData\UnitSeeder::class,
                InitialData\CategorySeeder::class,
                InitialData\PackingSeeder::class,
                InitialData\PortSeeder::class,
                InitialData\WarehouseSeeder::class,
                InitialData\ContactSeeder::class,
                InitialData\ProductSeeder::class,
                InitialData\AssortmentSeeder::class,
                InitialData\CustomsDataCategorySeeder::class,
            ];

            foreach ($seeders as $seeder) {
                $this->command?->info("Calling: {$seeder}");
                $this->callWith($seeder);
            }
        }

        // Fake data
        if ($force ?: app()->isLocal()) {
            // Skip seeders
            $skip = [
                'UserSeeder',
            ];
            $this->runSeeders('FakeData', $skip);
        }
    }

    // Helper methods

    public function getSeeders(string $folder): \Illuminate\Support\Collection
    {
        $this->command?->info("Discovering seeders in: {$folder}");

        $files = \Illuminate\Support\Facades\File::allFiles(database_path("seeders/{$folder}"));

        return collect($files)->map(function ($file) use ($folder) {
            $className = __NAMESPACE__ . "\\{$folder}\\" . strtr($file->getFilenameWithoutExtension(), '/', '\\');
            $this->command?->info("---> " . $file->getFilename());
            return $className;
        });
    }

    public function runSeeders(string $path_to_seeders, ?array $skip = []): void
    {
        $seeders = $this->getSeeders($path_to_seeders);

        if (!$seeders || $seeders->count() <= 0) {
            $this->command?->info('Nothing to Seed!');
        }

        $this->command?->info('Begin Data Seeding...');
        foreach ($seeders as $seeder) {
            $reflect = new \ReflectionClass($seeder);
            if (in_array($reflect->getShortName(), $skip)) {
                continue;
            }
            // Call Seeder
            $this->callWith($seeder);
        }
        $this->command?->info('Data Seeded Successfully!');
    }
}
