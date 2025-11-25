<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class ProcessCategoryCommand extends Command
{
    protected $signature = 'cus-data:category 
                            {--processes=3 : Số lượng process song song (tối đa 6)}
                            {--chunk-size=1000 : Kích thước chunk cho mỗi process}
                            {--max= : Tối đa số records cần xử lý (ví dụ: 500000)}
                            {--force : Buộc xử lý lại tất cả records}
                            {--timeout=3600 : Timeout cho mỗi process (giây)}
                            {--stats : Hiển thị performance stats chi tiết}';

    protected $description = 'Xử lý phân loại category cho CustomsData với đa luồng';

    protected string $keywordsHash;
    protected int $totalRecords = 0;
    protected int $processedRecords = 0;

    public function handle(): int
    {
        $startTime = microtime(true);

        $this->keywordsHash = CustomsDataCategory::currentKeywordsHash();
        $processes = (int) $this->option('processes');
        $chunkSize = (int) $this->option('chunk-size');
        $maxRecords = $this->option('max') ? (int) $this->option('max') : null;
        $force = $this->option('force');
        $timeout = (int) $this->option('timeout');
        $showStats = $this->option('stats');

        // Giới hạn processes để tránh overload
        if ($processes > 6) {
            $this->warn("⚠️ Giới hạn processes tối đa là 6 để tránh overload database");
            $processes = 6;
        }

        // Kiểm tra environment
        if (!function_exists('proc_open')) {
            $this->error("❌ proc_open function is not available!");
            $this->error("💡 Use 'php artisan cus-data:category-single' instead");
            return self::FAILURE;
        }

        // Hiển thị cấu hình
        $maxInfo = $maxRecords ? ", max: " . number_format($maxRecords) : "";
        $this->info("📊 Multi-process: {$processes} processes, chunk size: {$chunkSize}{$maxInfo}");
        $this->info("🔑 Keywords hash: {$this->keywordsHash}");

        // Lấy danh sách ID cần xử lý
        $recordIds = $this->getRecordIds($force, $maxRecords);

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

        // Xử lý các chunk song song
        $this->runParallelProcesses($chunks, $processes, $timeout, $progressBar);

        $progressBar->finish();
        $this->newLine();

        $totalTime = round(microtime(true) - $startTime, 2);
        $successRate = $this->totalRecords > 0 ? round(($this->processedRecords / $this->totalRecords) * 100, 2) : 0;

        $this->info("🎉 Hoàn thành xử lý {$this->processedRecords}/{$this->totalRecords} records ({$successRate}%)");

        if ($showStats) {
            $avgPerSecond = $totalTime > 0 ? round($this->processedRecords / $totalTime, 2) : 0;
            $this->info("⏱️ Thời gian: {$totalTime}s");
            $this->info("🚀 Tốc độ: {$avgPerSecond} records/giây");
            $this->info("📊 Chunks: {$totalChunks} chunks × {$chunkSize} records");
            $this->info("⚙️ Processes: {$processes} concurrent");
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

        // Tạo progress bar
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
     * Chạy các process song song
     */
    protected function runParallelProcesses(array $chunks, int $maxProcesses, int $timeout, $progressBar): void
    {
        $processes = [];
        $chunkIndex = 0;
        $completedChunks = 0;
        $allChunks = $chunks; // Store reference cho debug
        $stuckProcesses = []; // Track processes that might be stuck
        $lastProgressTime = time();

        while ($chunkIndex < count($chunks) || !empty($processes)) {
            // Khởi tạo process mới nếu còn chunk và chưa đạt giới hạn
            while (count($processes) < $maxProcesses && $chunkIndex < count($chunks)) {
                $chunk = $chunks[$chunkIndex];
                $process = $this->createWorkerProcess($chunk, $chunkIndex, $timeout);

                if ($process) {
                    $processes[$chunkIndex] = $process;
                    $chunkIndex++;

                    // Thêm delay giữa các process, tăng dần để giảm tải
                    $delay = min(200000, 50000 + ($chunkIndex * 2000)); // Tăng từ 0.05s lên 0.2s
                    if (count($processes) < $maxProcesses) {
                        usleep($delay);
                    }
                }
            }

            // Kiểm tra các process đã hoàn thành
            foreach ($processes as $index => $process) {
                if (!$process->isRunning()) {
                    $exitCode = $process->getExitCode();
                    $output = $process->getOutput();
                    $errorOutput = $process->getErrorOutput();

                    if ($exitCode === 0) {
                        // Thành công - cập nhật progress nhưng không log
                        $processedInChunk = 0;
                        if (preg_match('/Processed: (\d+)/', $output, $matches)) {
                            $processedInChunk = (int) $matches[1];
                            $this->processedRecords += $processedInChunk;
                        }
                    } else {
                        // Lỗi - hiển thị chi tiết
                        $this->error("❌ Process {$index} failed with exit code {$exitCode}");
                        $this->error("Command: {$process->getCommandLine()}");

                        if (!empty($errorOutput)) {
                            $this->error("=== ERROR OUTPUT ===");
                            $this->error($errorOutput);
                            $this->error("=== END ERROR ===");
                        }

                        if (!empty($output)) {
                            $this->error("=== STANDARD OUTPUT ===");
                            $this->error($output);
                            $this->error("=== END OUTPUT ===");
                        }

                        // Debug chunk info
                        if (isset($allChunks[$index])) {
                            $chunkIds = $allChunks[$index];
                            $this->error("🔍 Debug: Chunk {$index} chứa IDs: " . implode(',', array_slice($chunkIds, 0, 5)) . (count($chunkIds) > 5 ? '...' : ''));
                        }

                        // Log chi tiết để debug
                        Log::error("CustomsData Category Process {$index} failed", [
                            'exit_code' => $exitCode,
                            'error_output' => $errorOutput,
                            'standard_output' => $output,
                            'command' => $process->getCommandLine(),
                            'chunk_ids' => array_slice($chunkIds, 0, 10)
                        ]);
                    }

                    unset($processes[$index]);
                    $completedChunks++;
                    $progressBar->advance();
                }
            }

            // Kiểm tra process bị stuck - chỉ sau khi không có tiến triển trong thời gian dài
            // Tăng timeout và chỉ check khi có processes đang chạy
            $currentTime = time();
            $stuckTimeout = max(120, $timeout / 10); // Tối thiểu 2 phút hoặc 1/10 total timeout

            if (!empty($processes) && ($currentTime - $lastProgressTime > $stuckTimeout)) {
                // Kiểm tra có process nào thực sự bị stuck không (chạy quá lâu)
                $hasStuckProcess = false;
                foreach ($processes as $index => $process) {
                    if ($process->isRunning()) {
                        // Process chạy quá lâu so với timeout của nó
                        if ($currentTime - $process->getStartTime() > $timeout) {
                            $hasStuckProcess = true;
                            break;
                        }
                    }
                }

                if ($hasStuckProcess) {
                    $this->error("⚠️ Detected stuck processes after {$stuckTimeout}s - this may indicate shared hosting limitations");
                    $this->error("💡 Consider using: php artisan cus-data:category-single");

                    // Kill all running processes
                    foreach ($processes as $index => $process) {
                        if ($process->isRunning()) {
                            $process->stop();
                            $this->warn("Killed stuck process {$index}");
                        }
                    }
                    break;
                } else {
                    // Reset timer nếu processes vẫn đang chạy bình thường
                    $lastProgressTime = $currentTime;
                }
            }

            // Update progress time when processes complete
            if ($completedChunks > 0) {
                $lastProgressTime = time();
            }

            // Ngủ ngắn để không lãng phí CPU
            usleep(100000); // 0.1 giây
        }
    }

    /**
     * Tạo process worker để xử lý một chunk
     */
    protected function createWorkerProcess(array $chunk, int $chunkIndex, int $timeout): ?SymfonyProcess
    {
        $idsString = implode(',', $chunk);

        // Tạo process mà không log

        $command = [
            'php',
            '-d',
            'memory_limit=512M', // Set memory limit cho worker
            base_path('artisan'),
            'cus-data:category-worker',
            $idsString,
            $this->keywordsHash,
            (string) $chunkIndex
        ];

        try {
            $process = new SymfonyProcess($command);
            $process->setTimeout($timeout);
            $process->setWorkingDirectory(base_path());
            $process->start();

            Log::info("Started worker process {$chunkIndex}", [
                'command' => $process->getCommandLine(),
                'chunk_size' => count($chunk),
                'timeout' => $timeout
            ]);

            return $process;
        } catch (\Throwable $e) {
            $this->error("❌ Không thể tạo process {$chunkIndex}: {$e->getMessage()}");
            Log::error("Failed to create worker process {$chunkIndex}", [
                'error' => $e->getMessage(),
                'command' => implode(' ', $command)
            ]);
            return null;
        }
    }
}
