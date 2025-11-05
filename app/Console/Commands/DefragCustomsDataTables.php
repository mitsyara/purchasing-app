<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cron Job
 * 0 3 28-31 * * cd /home/bcprulfohosting/public_html/purchasing-app && /usr/local/bin/php artisan cus-data:optimize --rebuild >> /home/bcprulfohosting/public_html/purchasing-app/storage/logs/cus_data_cron_$(date +\%Y\%m).log 2>&1
 */

class DefragCustomsDataTables extends Command
{
    protected $signature = 'cus-data:optimize {--rebuild : Rebuild tables with ALTER TABLE ENGINE=InnoDB}';
    protected $description = 'Optimize/Defrag customs_data tables for MariaDB 10.5.29+';

    private function logInfo($message)
    {
        $this->info($message);
        Log::info($message);
    }

    public function handle()
    {
        if (date('Y-m-d') !== date('Y-m-t')) {
            $this->info("Not the last day of the month. Exiting.");
            return;
        }

        $database = 'mysql_customs_data';
        $tables = [
            'customs_data',
            'customs_data_categories',
            'customs_data_summaries',
        ];

        foreach ($tables as $table) {
            $this->logInfo("Starting optimization for table: $table");

            try {
                $connection = DB::connection($database);
                // 1. Analyze table
                $connection->statement("ANALYZE TABLE `$table`");
                $this->logInfo("✅ Analyzed");

                // 2. Optimize table
                $connection->statement("OPTIMIZE TABLE `$table`");
                $this->logInfo("✅ Optimized");

                // 3. Optional rebuild table
                if ($this->option('rebuild')) {
                    $connection->statement("ALTER TABLE `$table` ENGINE=InnoDB");
                    $this->logInfo("✅ Rebuilt table (ENGINE=InnoDB)");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error optimizing table $table: " . $e->getMessage());
                Log::error("Error optimizing table $table: " . $e->getMessage());
                continue;
            }

            $this->logInfo("Finished optimization for table: $table\n");
        }

        $this->logInfo("All customs_data tables optimized successfully!");
    }
}
