<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessCategorySingleCommand extends Command
{
    protected $signature = 'cus-data:category-single 
                            {--chunk-size=500 : Kích thước chunk}
                            {--max= : Tối đa số records cần xử lý (ví dụ: 500000)}
                            {--force : Buộc xử lý lại tất cả records}
                            {--stats : Hiển thị performance stats chi tiết}';

    protected $description = 'Xử lý phân loại category cho CustomsData (single-threaded cho shared hosting)';

    protected string $keywordsHash;
    protected int $totalRecords = 0;
    protected int $processedRecords = 0;

    public function handle(): int
    {
        $startTime = microtime(true);

        $this->keywordsHash = CustomsDataCategory::currentKeywordsHash();
        $chunkSize = (int) $this->option('chunk-size');
        $maxRecords = $this->option('max') ? (int) $this->option('max') : null;
        $force = $this->option('force');
        $showStats = $this->option('stats');

        // Validate keywords hash
        if (empty($this->keywordsHash)) {
            $this->error("❌ Keywords hash is empty! Check CustomsDataCategory model.");
            return self::FAILURE;
        }

        // Hiển thị cấu hình
        $maxInfo = $maxRecords ? ", max: " . number_format($maxRecords) : "";
        $this->info("📊 Single-threaded: chunk size: {$chunkSize}{$maxInfo}");
        $this->info("🔑 Keywords hash: {$this->keywordsHash}");

        // Lấy danh sách ID cần xử lý
        try {
            $recordIds = $this->getRecordIds($force, $maxRecords);
        } catch (\Throwable $e) {
            $this->error("❌ Lỗi khi lấy records: " . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($recordIds)) {
            $this->info("✅ Không có record nào cần xử lý");
            return self::SUCCESS;
        }

        $this->totalRecords = count($recordIds);
        $this->info("✅ Loaded " . number_format(count($recordIds)) . " records to process");
        $this->newLine();

        // Chia records thành các chunk
        $chunks = array_chunk($recordIds, $chunkSize);
        $totalChunks = count($chunks);

        // Tạo progress bar cho xử lý
        $progressBar = $this->output->createProgressBar($totalChunks);
        $progressBar->setFormat('Processing: %percent:s%% [%bar%] %current%/%max% chunks');
        $progressBar->start();

        // Xử lý từng chunk tuần tự
        $this->processSingleThreaded($chunks, $progressBar);

        $progressBar->finish();
        $this->newLine();

        // Stats
        $totalTime = round(microtime(true) - $startTime, 2);
        $successRate = $this->totalRecords > 0 ? round(($this->processedRecords / $this->totalRecords) * 100, 2) : 0;

        $this->info("🎉 Hoàn thành xử lý {$this->processedRecords}/{$this->totalRecords} records ({$successRate}%)");

        if ($showStats) {
            $avgPerSecond = $totalTime > 0 ? round($this->processedRecords / $totalTime, 2) : 0;
            $this->info("⏱️ Thời gian: {$totalTime}s");
            $this->info("🚀 Tốc độ: {$avgPerSecond} records/giây");
            $this->info("📊 Chunks: {$totalChunks} chunks × {$chunkSize} records");
            $this->info("⚙️ Mode: Single-threaded");
        }

        if ($this->processedRecords < $this->totalRecords) {
            $remaining = $this->totalRecords - $this->processedRecords;
            $this->warn("⚠️ Còn {$remaining} records chưa được xử lý. Chạy lại command để tiếp tục.");
        }

        return self::SUCCESS;
    }

    /**
     * Lấy danh sách ID records cần xử lý
     */
    protected function getRecordIds(bool $force, ?int $maxRecords = null): array
    {
        $query = CustomsData::on('mysql_customs_data')->select('id');

        if (!$force) {
            // Chỉ lấy records chưa xử lý với hash hiện tại
            $query->where(function ($q) {
                $q->whereNull('category_keywords_hash')
                    ->orWhere('category_keywords_hash', '!=', $this->keywordsHash);
            });
        }

        // Đếm tổng số records cần xử lý
        $totalAvailable = $query->count();
        $targetCount = $maxRecords && $maxRecords < $totalAvailable ? $maxRecords : $totalAvailable;

        $this->info("📊 Records: " . number_format($targetCount) . "/" . number_format($totalAvailable));

        $this->newLine();

        if ($totalAvailable === 0) {
            return [];
        }

        // Luôn sử dụng chunk loading để tiết kiệm memory
        $ids = [];
        $processed = 0;

        $loadProgress = $this->output->createProgressBar($targetCount);
        $loadProgress->setFormat('Loading IDs: %percent:s%% [%bar%] %current%/%max%');
        $loadProgress->start();

        $query->orderBy('id')->chunk(10000, function ($records) use (&$ids, &$processed, $targetCount, $maxRecords, $loadProgress) {
            $recordsToAdd = $records->pluck('id')->toArray();

            // Nếu có max limit, chỉ lấy đủ số lượng cần thiết
            if ($maxRecords && ($processed + count($recordsToAdd)) > $maxRecords) {
                $remaining = $maxRecords - $processed;
                $recordsToAdd = array_slice($recordsToAdd, 0, $remaining);
            }

            $ids = array_merge($ids, $recordsToAdd);
            $processed += count($recordsToAdd);
            $loadProgress->setProgress($processed);

            // Dừng khi đã đủ maxRecords
            if ($maxRecords && $processed >= $maxRecords) {
                return false; // Dừng chunk iteration
            }
        });

        $loadProgress->finish();
        $this->newLine();

        return $ids;
    }

    /**
     * Xử lý single-threaded
     */
    protected function processSingleThreaded(array $chunks, $progressBar): void
    {
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkStartTime = microtime(true);

            // Xử lý chunk mà không log

            // Show progress during processing for longer chunks
            $showProgress = count($chunk) > 200;
            $chunkProcessed = $this->processChunk($chunk, $chunkIndex, $showProgress);
            $this->processedRecords += $chunkProcessed;

            // Chunk hoàn thành - chỉ advance progress bar
            $progressBar->advance();

            // Memory cleanup
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Xử lý một chunk
     */
    protected function processChunk(array $chunkIds, int $chunkIndex, bool $showProgress = false): int
    {
        $processedCount = 0;

        try {
            // Xử lý từng batch nhỏ
            $batchSize = 100;
            $batches = array_chunk($chunkIds, $batchSize);

            foreach ($batches as $batchIndex => $batchIds) {
                // Xử lý batch mà không log

                try {
                    $records = CustomsData::on('mysql_customs_data')->whereIn('id', $batchIds)->get();

                    foreach ($records as $data) {
                        // Bỏ qua nếu đã xử lý với hash hiện tại
                        if ($data->category_keywords_hash === $this->keywordsHash) {
                            $processedCount++;
                            continue;
                        }

                        try {
                            $success = $data->guessCategoryByName($this->keywordsHash);
                            $processedCount++;

                            if ($success) {
                                Log::debug("Record {$data->id} assigned category {$data->customs_data_category_id}");
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Chunk {$chunkIndex} - Record {$data->id} failed: {$e->getMessage()}", [
                                'chunk_index' => $chunkIndex,
                                'record_id' => $data->id,
                                'error' => $e->getMessage()
                            ]);
                            $processedCount++; // Vẫn đếm là đã xử lý dù có lỗi
                        }
                    }

                    // Memory cleanup
                    unset($records);
                } catch (\Throwable $e) {
                    Log::error("Chunk {$chunkIndex} - Batch {$batchIndex} failed: {$e->getMessage()}", [
                        'chunk_index' => $chunkIndex,
                        'batch_index' => $batchIndex,
                        'batch_size' => count($batchIds),
                        'error' => $e->getMessage()
                    ]);
                    // Không throw để tiếp tục xử lý batch khác
                }

                // Micro sleep để không làm quá tải server
                usleep(10000); // 0.01 giây
            }
        } catch (\Throwable $e) {
            Log::error("Chunk {$chunkIndex} completely failed: {$e->getMessage()}", [
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage()
            ]);
        }

        return $processedCount;
    }
}
