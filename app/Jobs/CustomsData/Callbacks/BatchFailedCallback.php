<?php

namespace App\Jobs\CustomsData\Callbacks;

use App\Jobs\CustomsData\SequentialBatchSupervisorJob;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

class BatchFailedCallback
{
    protected int $userId;
    protected array $subBatches; // nếu cần
    protected int $index;

    public function __construct(int $userId, int $index)
    {
        $this->userId = $userId;
        $this->index = $index;
    }

    public function __invoke(Batch $batch, \Throwable $e)
    {
        Log::error("[CustomsDataBatch] ❌ Sub-batch {$this->index} failed: {$e->getMessage()}");

        Notification::make()
            ->title("Sub-batch {$this->index} Failed")
            ->body("An error occurred while processing sub-batch {$this->index}. Please check the logs for details.")
            ->danger()
            ->sendToDatabase([$this->userId]);
    }
}
