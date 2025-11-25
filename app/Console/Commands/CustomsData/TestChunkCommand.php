<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use Illuminate\Console\Command;

class TestChunkCommand extends Command
{
    protected $signature = 'cus-data:test-chunk {chunk-index}';
    protected $description = 'Test một chunk cụ thể để debug';

    public function handle(): int
    {
        $chunkIndex = (int) $this->argument('chunk-index');
        $keywordsHash = CustomsDataCategory::currentKeywordsHash();
        $chunkSize = 1000;

        $this->info("🔍 Testing chunk {$chunkIndex}");

        // Lấy records như command chính
        $query = CustomsData::on('mysql_customs_data')->select('id');
        $query->where(function ($q) use ($keywordsHash) {
            $q->whereNull('customs_data_category_id')
              ->orWhere('category_keywords_hash', '!=', $keywordsHash)
              ->orWhereNull('category_keywords_hash');
        });

        $recordIds = $query->pluck('id')->toArray();
        $chunks = array_chunk($recordIds, $chunkSize);

        if (!isset($chunks[$chunkIndex])) {
            $this->error("❌ Chunk {$chunkIndex} không tồn tại. Tổng chunks: " . count($chunks));
            return self::FAILURE;
        }

        $chunk = $chunks[$chunkIndex];
        $this->info("📊 Chunk {$chunkIndex} có " . count($chunk) . " IDs");
        $this->info("🔢 ID range: " . min($chunk) . " - " . max($chunk));

        // Test load records
        try {
            $records = CustomsData::on('mysql_customs_data')->whereIn('id', $chunk)->get();
            $this->info("✅ Load thành công {$records->count()} records");

            // Test process một vài records
            $testCount = min(5, $records->count());
            $this->info("🧪 Testing {$testCount} records đầu tiên...");

            foreach ($records->take($testCount) as $data) {
                try {
                    $this->info("  - ID {$data->id}: {$data->product}");
                    
                    if ($data->category_keywords_hash === $keywordsHash) {
                        $this->info("    ✅ Đã có hash hiện tại");
                        continue;
                    }

                    $success = $data->guessCategoryByName($keywordsHash);
                    if ($success) {
                        $this->info("    ✅ Assigned category: {$data->customs_data_category_id}");
                    } else {
                        $this->info("    ➖ Không match category nào");
                    }
                } catch (\Throwable $e) {
                    $this->error("    ❌ Error: {$e->getMessage()}");
                    $this->error("    📍 File: {$e->getFile()}:{$e->getLine()}");
                }
            }

            $this->info("✅ Test chunk hoàn thành");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Error khi load records: {$e->getMessage()}");
            $this->error("📍 File: {$e->getFile()}:{$e->getLine()}");
            $this->error("🔍 Trace: {$e->getTraceAsString()}");
            return self::FAILURE;
        }
    }
}