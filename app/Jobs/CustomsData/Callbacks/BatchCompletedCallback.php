<?php

namespace App\Jobs\CustomsData\Callbacks;

use App\Jobs\CustomsData\SequentialBatchSupervisorJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

class BatchCompletedCallback
{
    protected int $userId;
    protected array $subBatches;
    protected int $totalRecords;
    protected int $index;

    public function __construct(int $userId, array $subBatches, int $totalRecords, int $index)
    {
        $this->userId = $userId;
        $this->subBatches = $subBatches;
        $this->totalRecords = $totalRecords;
        $this->index = $index;
    }

    public function __invoke(Batch $batch)
    {
        Log::info("[CustomsDataBatch] ✅ Sub-batch {$this->index} completed");

        $next = new \App\Jobs\CustomsData\SequentialBatchSupervisorJob(
            $this->userId,
            $this->subBatches,
            $this->totalRecords
        );

        $next->setCurrentIndex($this->index); // dispatch next sub-batch
        dispatch($next);
    }
}
