<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCustomsDataByImporterJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        $connection = DB::connection('mysql_customs_data');

        // Table names
        $mainTable = 'customs_data_by_importers';
        $tempTable = 'temp_customs_data_by_importers';
        $backupTable = 'old_customs_data_by_importers';

        try {
            // Step 1: Drop temp if exists
            $connection->statement("DROP TABLE IF EXISTS {$tempTable}");

            // Step 2: Create temp clone from main
            $connection->statement("CREATE TABLE {$tempTable} LIKE {$mainTable}");

            // Step 3: Ensure import_month & is_vett columns exist
            try {
                $connection->statement("
                    ALTER TABLE {$tempTable}
                    ADD COLUMN import_month CHAR(7) NULL COMMENT 'YYYY-MM' AFTER importer
                ");
                $connection->statement("CREATE INDEX idx_import_month ON {$tempTable}(import_month)");
            } catch (\Throwable $e) {
                // ignore if exists
            }

            try {
                $connection->statement("
                    ALTER TABLE {$tempTable}
                    ADD COLUMN is_vett BOOLEAN NOT NULL DEFAULT 0 COMMENT '1 = veterinary-related' AFTER total_value
                ");
                $connection->statement("CREATE INDEX idx_is_vett ON {$tempTable}(is_vett)");
            } catch (\Throwable $e) {
                // ignore if exists
            }

            // Step 4: Disable ONLY_FULL_GROUP_BY for this session
            try {
                $connection->statement("
                    SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))
                ");
            } catch (\Throwable $e) {
                Log::warning("Unable to adjust sql_mode: " . $e->getMessage());
            }

            // Step 5: Aggregate data safely into temp
            $connection->statement("
                INSERT INTO {$tempTable} (
                    importer,
                    import_month,
                    total_import,
                    total_qty,
                    total_value,
                    is_vett
                )
                SELECT importer,
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
                GROUP BY importer, DATE_FORMAT(import_date, '%Y-%m')
            ");

            // Step 6: Atomic swap in transaction
            $connection->beginTransaction();

            // Drop backup if exists
            $connection->statement("DROP TABLE IF EXISTS {$backupTable}");

            // Atomic rename (safe swap)
            $connection->statement("
                RENAME TABLE {$mainTable} TO {$backupTable},
                             {$tempTable} TO {$mainTable}
            ");

            $connection->commit();

            // Step 7: Drop old backup after success
            $connection->statement("DROP TABLE IF EXISTS {$backupTable}");

            // Step 8: Optimize new table stats
            try {
                $connection->statement("ANALYZE TABLE {$mainTable}");
            } catch (\Throwable $e) {
                Log::warning("Analyze table failed: " . $e->getMessage());
            }

            Log::info("RecalculateCustomsDataByImporterJob completed successfully.");
        } catch (\Throwable $e) {
            Log::error("RecalculateCustomsDataByImporterJob failed: " . $e->getMessage());

            // Rollback if active
            try {
                $connection->rollBack();
            } catch (\Throwable $rollbackError) {
                Log::warning("Rollback failed or not needed: " . $rollbackError->getMessage());
            }

            // Try restore from backup
            try {
                $tables = $connection->select("SHOW TABLES LIKE '{$backupTable}'");
                if ($tables) {
                    $connection->statement("
                        RENAME TABLE {$backupTable} TO {$mainTable}
                    ");
                    Log::info("Restored {$mainTable} from backup after failure.");
                }
            } catch (\Throwable $restoreError) {
                Log::error("Restore failed: " . $restoreError->getMessage());
            }

            // TODO: Add notification (email, Slack...) if needed
        }
    }
}
