<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateCustomsDataSummaryImproved extends Command
{
    protected $signature = 'cus-data:summary 
                            {--force : Recalculate entire summary}
                            {--batch-size=10000 : Batch size for processing}
                            {--chunk-size=500 : Chunk size for SQL insert operations}
                            {--start-date= : Start date for processing (YYYY-MM-DD)}
                            {--end-date= : End date for processing (YYYY-MM-DD)}';

    protected $description = 'Generate summary tables from CustomsData (Improved Version)';

    private $connection;
    private $batchSize;
    private $chunkSize;
    private $mainTable = 'customs_data_summaries';
    private $tempTable = 'customs_data_summaries_temp';
    private $backupTable = 'customs_data_summaries_backup';

    public function handle(): void
    {
        $this->connection = DB::connection('mysql_customs_data');
        $this->batchSize = (int) $this->option('batch-size');
        $this->chunkSize = (int) $this->option('chunk-size');

        $this->logInfo('=== Starting CustomsDataSummary Generation ===', [
            'batch_size' => $this->batchSize,
            'chunk_size' => $this->chunkSize,
            'force' => $this->option('force')
        ]);

        try {
            if ($this->option('force')) {
                $this->handleForceMode();
            } else {
                $this->handleIncrementalMode();
            }
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    private function handleForceMode(): void
    {
        $this->logInfo('Force mode: Recalculating entire summary with batching');

        // Create temp table
        $this->createTempTable();

        // Process aggregated data directly without problematic batching
        $this->logInfo('Aggregating data into temporary table...');

        // Remove transaction wrapper - execute directly
        $this->connection->statement("
            INSERT INTO `{$this->tempTable}` (importer, customs_data_category_id, import_date, total_import, total_qty, total_value, is_vett, created_at, updated_at)
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

        // Swap tables
        $this->swapTables();

        $this->logInfo('=== Force mode completed successfully ===');
        return;
    }

    private function handleIncrementalMode(): void
    {
        $this->logInfo('Incremental mode: Processing new data only');

        $dateRange = $this->getIncrementalDateRange();
        if (!$dateRange) {
            $this->logInfo('No new data to process');
            return;
        }

        [$startDate, $endDate] = $dateRange;
        $this->logInfo("Processing data from {$startDate} to {$endDate}", [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $this->processIncrementalData($startDate, $endDate);

        $this->logInfo('=== Incremental mode completed successfully ===');
        return;
    }

    private function createTempTable()
    {
        $this->connection->statement("DROP TABLE IF EXISTS `{$this->tempTable}`");
        $this->connection->statement("CREATE TABLE `{$this->tempTable}` LIKE `{$this->mainTable}`");
    }

    private function getIncrementalDateRange(): ?array
    {
        // Check if summary table exists first
        if (!$this->connection->getSchemaBuilder()->hasTable($this->mainTable)) {
            $this->logInfo('Summary table does not exist. Will create from scratch.');
            return null; // This will trigger full calculation
        }

        $summaryMaxDate = $this->connection->table($this->mainTable)->max('import_date');
        $customsMaxDate = $this->connection->table('customs_data')->max('import_date');

        if (!$customsMaxDate) {
            return null;
        }

        $startDate = $this->option('start-date') ?: ($summaryMaxDate ? Carbon::parse($summaryMaxDate)->addDay()->format('Y-m-d') : null);

        $endDate = $this->option('end-date') ?: $customsMaxDate;

        if (!$startDate || $startDate > $endDate) {
            return null;
        }

        return [$startDate, $endDate];
    }

    private function processIncrementalData(string $startDate, string $endDate): void
    {
        $totalRecords = $this->connection->table('customs_data')
            ->whereBetween('import_date', [$startDate, $endDate])
            ->count();

        $this->logInfo("Processing {$totalRecords} records incrementally", [
            'total_records' => $totalRecords,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $bar = $this->output->createProgressBar(ceil($totalRecords / $this->batchSize));
        $bar->start();

        $offset = 0;
        do {
            $rows = $this->connection->table('customs_data')
                ->selectRaw('
                    importer,
                    customs_data_category_id,
                    import_date,
                    COUNT(*) as total_import,
                    SUM(qty) as total_qty,
                    SUM(value) as total_value,
                    MAX(is_vett) as is_vett
                ')
                ->whereBetween('import_date', [$startDate, $endDate])
                ->groupBy('importer', 'customs_data_category_id', 'import_date')
                ->offset($offset)
                ->limit($this->batchSize)
                ->get();

            // Process in smaller chunks to avoid SQL length issues
            $incrementalChunkSize = max(100, $this->chunkSize / 5); // Chia nhỏ hơn cho incremental (mặc định 100)
            $chunks = $rows->chunk($incrementalChunkSize);
            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    $this->connection->table($this->mainTable)->updateOrInsert(
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
            }

            $offset += $this->batchSize;
            $bar->advance();
        } while ($rows->count() === $this->batchSize);

        $bar->finish();
        $this->line('');
    }

    private function swapTables(): void
    {
        try {
            // Drop old backup if exists
            $this->connection->statement("DROP TABLE IF EXISTS `{$this->backupTable}`");
            $this->logInfo('Old backup dropped.');

            // Backup current main table (if exists)
            if ($this->connection->getSchemaBuilder()->hasTable($this->mainTable)) {
                $this->connection->statement("RENAME TABLE `{$this->mainTable}` TO `{$this->backupTable}`");
                $this->logInfo('Current table backed up successfully.');
            }

            // Promote temp table to main table
            $this->connection->statement("RENAME TABLE `{$this->tempTable}` TO `{$this->mainTable}`");
            $this->logInfo('New table promoted successfully.');
            
            $this->logInfo('Table swap completed successfully');
        } catch (\Throwable $swapError) {
            $this->logError('Table swap failed', $swapError);
            
            // If swap failed, try to rollback
            try {
                if ($this->connection->getSchemaBuilder()->hasTable($this->backupTable) && 
                    !$this->connection->getSchemaBuilder()->hasTable($this->mainTable)) {
                    $this->connection->statement("RENAME TABLE `{$this->backupTable}` TO `{$this->mainTable}`");
                    $this->logInfo('Rollback from backup successful.');
                }
            } catch (\Throwable $rollbackError) {
                $this->logError('Rollback also failed', $rollbackError);
            }
            
            throw $swapError; // Re-throw to trigger main error handler
        }
    }

    private function handleError(\Throwable $e): void
    {
        $this->logError('CustomsDataSummary generation failed', $e);

        // Cleanup and rollback
        try {
            // If temp table exists, drop it
            if ($this->connection->getSchemaBuilder()->hasTable($this->tempTable)) {
                $this->connection->statement("DROP TABLE IF EXISTS `{$this->tempTable}`");
                $this->logInfo('Cleaned up temporary table.');
            }

            // If backup exists and main table is missing/corrupted, restore backup
            if ($this->connection->getSchemaBuilder()->hasTable($this->backupTable)) {
                if (!$this->connection->getSchemaBuilder()->hasTable($this->mainTable)) {
                    $this->connection->statement("RENAME TABLE `{$this->backupTable}` TO `{$this->mainTable}`");
                    $this->logInfo('Restored from backup table.', ['action' => 'restore_from_backup']);
                }
            }
        } catch (\Throwable $cleanupError) {
            $this->logError('Cleanup/Rollback failed', $cleanupError);
        }
    }

    /**
     * Helper function to log info messages to both console and log file
     */
    private function logInfo(string $message, array $context = []): void
    {
        $this->info($message);
        Log::info($message, $context);
    }

    /**
     * Helper function to log error messages to both console and log file
     */
    private function logError(string $message, ?\Throwable $exception = null, array $context = []): void
    {
        $displayMessage = $exception ? "{$message}: {$exception->getMessage()}" : $message;
        $this->error($displayMessage);
        
        $logContext = $context;
        if ($exception) {
            $logContext['error'] = $exception->getMessage();
            $logContext['trace'] = $exception->getTraceAsString();
        }
        
        Log::error($message, $logContext);
    }
}