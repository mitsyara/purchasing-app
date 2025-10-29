<?php

namespace App\Jobs\CustomsData;

use App\Models\CustomsData;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBatchCustomsDataCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $backoff = 10;

    public function __construct(
        protected array $customsDataIds,
        protected string $keywordsHash
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        try {
            foreach (array_chunk($this->customsDataIds, 200) as $chunk) {
                $records = CustomsData::whereIn('id', $chunk)->get();

                foreach ($records as $data) {
                    if ($data->category_keywords_hash === $this->keywordsHash) {
                        continue;
                    }

                    try {
                        $data->guessCategoryByName($this->keywordsHash);
                    } catch (\Throwable $e) {
                        Log::warning("[CustomsDataBatch] Record {$data->id} failed: {$e->getMessage()}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("[CustomsDataBatch] Job error: {$e->getMessage()}");
            throw $e;
        }
    }
}
