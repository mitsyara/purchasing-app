<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCustomsDataByImporterCategoryJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $connection = DB::connection('mysql_customs_data');

        $mainTable   = 'customs_data_by_importer_categories';
        $tempTable   = 'temp_customs_data_by_importer_categories';
        $backupTable = 'old_customs_data_by_importer_categories';

        try {
            Log::info('[CustomsJob] Step 1: Drop temp table if exists');
            $connection->statement("DROP TABLE IF EXISTS {$tempTable}");

            Log::info('[CustomsJob] Step 2: Create temp table clone');
            $connection->statement("CREATE TABLE {$tempTable} LIKE {$mainTable}");

            Log::info('[CustomsJob] Step 3: Ensure extra columns exist');
            try {
                $connection->statement("
                ALTER TABLE {$tempTable}
                ADD COLUMN import_month CHAR(7) NULL COMMENT 'YYYY-MM' AFTER importer
            ");
                $connection->statement("CREATE INDEX idx_import_month ON {$tempTable}(import_month)");
            } catch (\Throwable $e) {
                Log::debug('[CustomsJob] import_month already exists: ' . $e->getMessage());
            }

            try {
                $connection->statement("
                ALTER TABLE {$tempTable}
                ADD COLUMN is_vett BOOLEAN NOT NULL DEFAULT 0 COMMENT '1 = veterinary-related' AFTER total_value
            ");
                $connection->statement("CREATE INDEX idx_is_vett ON {$tempTable}(is_vett)");
            } catch (\Throwable $e) {
                Log::debug('[CustomsJob] is_vett already exists: ' . $e->getMessage());
            }

            Log::info('[CustomsJob] Step 4: Disable ONLY_FULL_GROUP_BY');
            try {
                $connection->statement("
                SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))
            ");
            } catch (\Throwable $e) {
                Log::warning('[CustomsJob] Unable to adjust sql_mode: ' . $e->getMessage());
            }

            Log::info('[CustomsJob] Step 5: Insert aggregated data into temp table');
            $connection->statement("
            INSERT INTO {$tempTable} (
                importer,
                customs_data_category_id,
                import_month,
                total_import,
                total_qty,
                total_value,
                is_vett
            )
            SELECT importer,
                   customs_data_category_id,
                   DATE_FORMAT(import_date, '%Y-%m') AS import_month,
                   COUNT(product) AS total_import,
                   SUM(qty) AS total_qty,
                   SUM(value) AS total_value,
                   MAX(
                       CASE
                           WHEN LOWER(importer) LIKE '%thu y%'
                             OR LOWER(importer) LIKE '%thú y%'
                             OR LOWER(importer) LIKE '%veterinary%'
                             OR LOWER(product) LIKE '%thu y%'
                             OR LOWER(product) LIKE '%thú y%'
                             OR LOWER(product) LIKE '%veterinary%'
                           THEN 1 ELSE 0
                       END
                   ) AS is_vett
            FROM customs_data
            GROUP BY importer, customs_data_category_id, DATE_FORMAT(import_date, '%Y-%m')
        ");

            Log::info('[CustomsJob] Step 6: Start transaction for table swap');
            $connection->beginTransaction();

            Log::info('[CustomsJob] Step 7: Drop old backup if exists');
            $connection->statement("DROP TABLE IF EXISTS {$backupTable}");

            Log::info('[CustomsJob] Step 8: Rename tables (swap main <-> temp)');
            $connection->statement("
            RENAME TABLE {$mainTable} TO {$backupTable},
                         {$tempTable} TO {$mainTable}
        ");

            $connection->commit();
            Log::info('[CustomsJob] Step 9: Commit successful');

            Log::info('[CustomsJob] Step 10: Drop old backup');
            $connection->statement("DROP TABLE IF EXISTS {$backupTable}");

            Log::info('[CustomsJob] Step 11: Analyze table for optimizer stats');
            try {
                $connection->statement("ANALYZE TABLE {$mainTable}");
            } catch (\Throwable $e) {
                Log::warning('[CustomsJob] Analyze table failed: ' . $e->getMessage());
            }

            Log::info('[CustomsJob] ✅ Job completed successfully.');
        } catch (\Throwable $e) {
            Log::error('[CustomsJob] ❌ Failed at: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Rollback if transaction is active
            try {
                if (method_exists($connection, 'transactionLevel') && $connection->transactionLevel() > 0) {
                    $connection->rollBack();
                    Log::warning('[CustomsJob] Transaction rolled back.');
                } else {
                    Log::debug('[CustomsJob] No active transaction to roll back.');
                }
            } catch (\Throwable $rollbackError) {
                Log::warning('[CustomsJob] Rollback failed: ' . $rollbackError->getMessage());
            }

            // Try to restore from backup
            try {
                $tables = $connection->select("SHOW TABLES LIKE '{$backupTable}'");
                if ($tables) {
                    $connection->statement("RENAME TABLE {$backupTable} TO {$mainTable}");
                    Log::info("[CustomsJob] Restored {$mainTable} from backup.");
                }
            } catch (\Throwable $restoreError) {
                Log::error('[CustomsJob] Restore failed: ' . $restoreError->getMessage());
            }
        }
    }
}
