<?php

namespace App\Jobs\CustomsData;

use App\Jobs\CustomsData\Callbacks\BatchCompletedCallback;
use App\Jobs\CustomsData\Callbacks\BatchFailedCallback;
use App\Jobs\CustomsData\Callbacks\BatchFinallyCallback;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class SequentialBatchSupervisorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $currentIndex = 0;

    public function __construct(
        protected int $userId,
        protected array $subBatches,
        protected int $totalRecords
    ) {}

    public function handle(): void
    {
        if (!isset($this->subBatches[$this->currentIndex])) {
            Log::info("[CustomsDataBatch] ✅ All sub-batches done for user {$this->userId}");
            return;
        }

        $currentJobs = $this->subBatches[$this->currentIndex];
        $index = $this->currentIndex + 1;
        $total = count($this->subBatches);

        Log::info("[CustomsDataBatch] ▶️ Dispatching sub-batch {$index}/{$total}");

        $batch = Bus::batch($currentJobs)
            ->name("CustomsData-User-{$this->userId}-SubBatch-{$index}")
            ->then(new BatchCompletedCallback(
                $this->userId,
                $this->subBatches,
                $this->totalRecords,
                $index
            ))
            ->catch(new BatchFailedCallback(
                $this->userId,
                $index
            ))
            ->finally(new BatchFinallyCallback(
                $this->userId,
                count($this->subBatches),
                $index
            ))
            ->dispatch();

        Log::info("[CustomsDataBatch] 🚀 Dispatched sub-batch {$index} ({$batch->totalJobs} jobs)");
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSubBatches(): array
    {
        return $this->subBatches;
    }

    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    public function setCurrentIndex(int $index): void
    {
        $this->currentIndex = $index;
    }
}
