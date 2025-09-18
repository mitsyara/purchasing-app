<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $initial = true;
        $test = false;

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
            ];

            foreach ($seeders as $seeder) {
                $this->command?->info("Calling: {$seeder}");
                $this->callWith($seeder);
            }
        }

        // Fake data
        if ($test) {
            // Skip seeders
            $skip = [
                'UserSeeder',
            ];
            $this->runSeeders('seeders\FakeData', $skip);
        }
    }

    /**
     * Helper methods
     */
    public function getSeeders(string $path_to_seeders): \Illuminate\Support\Collection
    {
        $this->command?->info('Discovering Seeders');
        return collect(\Illuminate\Support\Facades\File::allFiles(database_path($path_to_seeders)))
            ->map(function ($item) use ($path_to_seeders) {
                $path = $item->getRelativePathname();
                $folder_path = \Illuminate\Support\Str::after($path_to_seeders, 'seeders\\');
                $class = __NAMESPACE__ . "\\{$folder_path}\\" . strtr(substr($path, 0, strrpos($path, '.')), '/', '\\');
                $this->command?->info('---> ' . $path);
                return $class . ' || ' . $folder_path;
            })
            ->values();
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
