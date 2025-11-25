<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCategoryWorkerCommand extends Command
{
    protected $signature = 'cus-data:category-worker {ids} {keywords-hash} {chunk-index}';
    protected $description = 'Worker process để xử lý một chunk CustomsData category';

    public function handle(): int
    {
        $idsString = $this->argument('ids');
        $keywordsHash = $this->argument('keywords-hash');
        $chunkIndex = $this->argument('chunk-index');

        try {
            // Validate arguments
            if (empty($idsString) || empty($keywordsHash)) {
                throw new \InvalidArgumentException("Missing required arguments");
            }
            
            $ids = array_map('intval', explode(',', $idsString));
            $processedCount = 0;

            // Log bắt đầu xử lý
            Log::info("Worker {$chunkIndex} started", [
                'chunk_index' => $chunkIndex,
                'total_ids' => count($ids),
                'keywords_hash' => $keywordsHash,
                'first_id' => $ids[0] ?? null,
                'last_id' => end($ids) ?: null
            ]);

            // Xử lý từng batch nhỏ để tránh memory issue - giảm batch size cho ổn định
            $batchSize = 100; // Giảm từ 200 xuống 100
            $batches = array_chunk($ids, $batchSize);

            foreach ($batches as $batchIndex => $batchIds) {
                $batchStartTime = microtime(true);
                
                try {
                    $records = CustomsData::on('mysql_customs_data')->whereIn('id', $batchIds)->get();
                    
                    Log::debug("Worker {$chunkIndex} - Batch {$batchIndex}: loaded {$records->count()} records");

                    $actuallyProcessed = 0;
                    $skipped = 0;
                    
                    foreach ($records as $data) {
                        // Bỏ qua nếu đã xử lý với hash hiện tại
                        if ($data->category_keywords_hash === $keywordsHash) {
                            $skipped++;
                            continue;
                        }

                        try {
                            $success = $data->guessCategoryByName($keywordsHash);
                            $actuallyProcessed++;

                            if ($success) {
                                Log::debug("Record {$data->id} assigned category {$data->customs_data_category_id}");
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Worker {$chunkIndex} - Record {$data->id} failed: {$e->getMessage()}", [
                                'chunk_index' => $chunkIndex,
                                'record_id' => $data->id,
                                'error' => $e->getMessage()
                            ]);
                            $actuallyProcessed++; // Vẫn đếm là đã xử lý dù có lỗi
                        }
                    }
                    
                    $processedCount += $actuallyProcessed;
                    
                    if ($skipped > 0) {
                        Log::debug("Worker {$chunkIndex} - Batch {$batchIndex}: processed {$actuallyProcessed}, skipped {$skipped} (already have correct hash)");
                    }
                    
                    $batchTime = round(microtime(true) - $batchStartTime, 2);
                    Log::debug("Worker {$chunkIndex} - Batch {$batchIndex} completed in {$batchTime}s");
                    
                } catch (\Throwable $e) {
                    Log::error("Worker {$chunkIndex} - Batch {$batchIndex} failed: {$e->getMessage()}", [
                        'chunk_index' => $chunkIndex,
                        'batch_index' => $batchIndex,
                        'batch_size' => count($batchIds),
                        'error' => $e->getMessage()
                    ]);
                    throw $e; // Re-throw để worker process fail
                }

                // Aggressive memory cleanup
                unset($records);
                
                // Force garbage collection sau mỗi batch + clear cache
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                
                // Clear model cache nếu có
                if (method_exists(CustomsData::class, 'flushEventListeners')) {
                    CustomsData::flushEventListeners();
                }
                
                // Ngủ nhỏ giữa các batch để giảm tải CPU
                usleep(10000); // 0.01 giây
            }

            // Output kết quả để parent process đọc
            $this->line("Processed: {$processedCount}");

            Log::info("Worker {$chunkIndex} completed", [
                'chunk_index' => $chunkIndex,
                'processed_count' => $processedCount,
                'total_ids' => count($ids)
            ]);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $errorMessage = "Worker {$chunkIndex} error: {$e->getMessage()}";
            $this->error($errorMessage);
            
            // Output error để parent process có thể đọc
            $this->error("WORKER_ERROR: {$e->getMessage()}");
            $this->error("WORKER_TRACE: {$e->getTraceAsString()}");
            
            Log::error("Worker {$chunkIndex} failed", [
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return self::FAILURE;
        }
    }
}
