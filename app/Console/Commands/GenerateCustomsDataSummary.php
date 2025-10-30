<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateCustomsDataSummary extends Command
{
    protected $signature = 'cus-data:summary-old {--force : Recalculate entire summary}';
    protected $description = 'Generate summary tables from CustomsData';

    public function handle()
    {
        $force = $this->option('force');

        $this->info('=== Starting CustomsDataSummary table generation ===');
        Log::info('=== Starting CustomsDataSummary table generation ===');

        $connection = DB::connection('mysql_customs_data');

        $mainTable = 'customs_data_summaries';
        $tempTable = 'customs_data_summaries_temp';
        $backupTable = 'customs_data_summaries_backup';

        try {
            if ($force) {
                // FORCE MODE: Recalculate entire summary
                $this->info('--force detected: calculating all data.');
                Log::info('Force mode: calculating entire summary.');

                // Drop temp table if exists
                $connection->statement("DROP TABLE IF EXISTS `$tempTable`");
                $connection->statement("CREATE TABLE `$tempTable` LIKE `$mainTable`");

                // Insert aggregated data into temp table
                $this->info('Aggregating all data into temporary table...');
                Log::info('Populating temporary table with full data...');

                $connection->transaction(function () use ($connection, $tempTable) {
                    $connection->statement("
                        INSERT INTO `$tempTable` (importer, customs_data_category_id, import_date, total_import, total_qty, total_value, is_vett, created_at, updated_at)
                        SELECT
                            importer,
                            customs_data_category_id,
                            import_date,
                            COUNT(id) AS total_import,
                            SUM(qty) AS total_qty,
                            SUM(value) AS total_value,
                            MAX(is_vett) AS is_vett,
                            NOW() AS created_at,
                            NOW() AS updated_at
                        FROM `customs_data`
                        GROUP BY importer, customs_data_category_id, import_date
                    ");
                });

                // Backup old summary table
                $connection->statement("DROP TABLE IF EXISTS `$backupTable`");
                $connection->statement("RENAME TABLE `$mainTable` TO `$backupTable`");

                // Swap temp table to main table
                $connection->statement("RENAME TABLE `$tempTable` TO `$mainTable`");

                $this->info('=== Summary table generated successfully (full data) ===');
                Log::info('=== CustomsDataSummary generated successfully (full) ===');
            } else {
                // INCREMENTAL MODE: Only update new data
                $this->info('Running incremental mode: updating only new data.');
                Log::info('Incremental mode: updating only new data.');

                $summaryMaxDate = $connection->table($mainTable)->max('import_date');
                $customsMaxDate = $connection->table('customs_data')->max('import_date');

                if (!$customsMaxDate) {
                    $this->info('No data in customs_data table.');
                    Log::info('No data in customs_data.');
                    return;
                }

                if ($summaryMaxDate) {
                    $startDate = Carbon::parse($summaryMaxDate)->addDay()->format('Y-m-d');
                } else {
                    $startDate = null;
                }

                if ($startDate) {
                    $this->info("Updating data from $startDate to $customsMaxDate");
                    Log::info("Updating summary from $startDate to $customsMaxDate");

                    $rows = $connection->table('customs_data')
                        ->selectRaw('importer, customs_data_category_id, import_date, COUNT(*) as total_import, SUM(qty) as total_qty, SUM(value) as total_value, MAX(is_vett) as is_vett')
                        ->whereBetween('import_date', [$startDate, $customsMaxDate])
                        ->groupBy('importer', 'customs_data_category_id', 'import_date')
                        ->get();

                    foreach ($rows as $row) {
                        $connection->table($mainTable)->updateOrInsert(
                            [
                                'importer' => $row->importer,
                                'customs_data_category_id' => $row->customs_data_category_id,
                                'import_date' => $row->import_date,
                            ],
                            [
                                'total_import' => $row->total_import,
                                'total_qty' => $row->total_qty,
                                'total_value' => $row->total_value,
                                'is_vett' => $row->is_vett,
                                'updated_at' => now(),
                            ]
                        );
                    }

                    $this->info('=== Summary table updated successfully (incremental) ===');
                    Log::info('=== CustomsDataSummary updated successfully (incremental) ===');
                } else {
                    // summary table empty, calculate all
                    $this->info('Summary table empty: calculating all data.');
                    Log::info('Summary table empty: calculating all data.');

                    // fallback: run same logic as --force
                    $connection->statement("DROP TABLE IF EXISTS `$tempTable`");
                    $connection->statement("CREATE TABLE `$tempTable` LIKE `$mainTable`");

                    $connection->transaction(function () use ($connection, $tempTable) {
                        $connection->statement("
                            INSERT INTO `$tempTable` (importer, customs_data_category_id, import_date, total_import, total_qty, total_value, is_vett, created_at, updated_at)
                            SELECT
                                importer,
                                customs_data_category_id,
                                import_date,
                                COUNT(*) AS total_import,
                                SUM(qty) AS total_qty,
                                SUM(value) AS total_value,
                                MAX(is_vett) AS is_vett,
                                NOW() AS created_at,
                                NOW() AS updated_at
                            FROM `customs_data`
                            GROUP BY importer, customs_data_category_id, import_date
                        ");
                    });

                    $connection->statement("DROP TABLE IF EXISTS `$backupTable`");
                    $connection->statement("RENAME TABLE `$mainTable` TO `$backupTable`");
                    $connection->statement("RENAME TABLE `$tempTable` TO `$mainTable`");

                    $this->info('=== Summary table generated successfully (fallback full data) ===');
                    Log::info('=== CustomsDataSummary generated successfully (fallback full) ===');
                }
            }
        } catch (\Throwable $e) {
            $this->error('Error generating CustomsDataSummary table: ' . $e->getMessage());
            Log::error('Error generating CustomsDataSummary: ' . $e->getMessage());

            // Rollback table if needed: delete temp table, restore backup
            try {
                if ($connection->getSchemaBuilder()->hasTable($tempTable)) {
                    $connection->statement("DROP TABLE IF EXISTS `$tempTable`");
                }

                if ($connection->getSchemaBuilder()->hasTable($backupTable)) {
                    if (!$connection->getSchemaBuilder()->hasTable($mainTable)) {
                        $connection->statement("RENAME TABLE `$backupTable` TO `$mainTable`");
                    }
                }

                $this->info('Rollback completed.');
                Log::info('Rollback completed.');
            } catch (\Throwable $rollbackException) {
                $this->error('Rollback failed: ' . $rollbackException->getMessage());
                Log::error('Rollback failed: ' . $rollbackException->getMessage());
            }
        }
    }
}
