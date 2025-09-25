<?php
namespace App\Jobs;

use App\Models\CustomsData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCustomsDataCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 3;
    public int $backoff = 10; // seconds

    protected int $customsDataId;
    protected string $keywordsHash;

    public function __construct(int $customsDataId, string $keywordsHash)
    {
        $this->customsDataId = $customsDataId;
        $this->keywordsHash = $keywordsHash;
    }

    public function handle(): void
    {
        try {
            $data = CustomsData::find($this->customsDataId);
            if (!$data) return;

            if ($data->category_keywords_hash === $this->keywordsHash) return;

            $data->guessCategoryByName($this->keywordsHash);

        } catch (\Throwable $e) {
            logger()->error("❌ Error processing CustomsData ID {$this->customsDataId}: {$e->getMessage()}");
            throw $e; // for retry
        }
    }
}