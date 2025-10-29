<?php

namespace App\Jobs\CustomsData\Callbacks;

use App\Jobs\CustomsData\SequentialBatchSupervisorJob;
use Filament\Notifications\Notification;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

class BatchFinallyCallback
{
    protected int $userId;
    protected int $totalSubBatches;
    protected int $index;

    public function __construct(int $userId, int $totalSubBatches, int $index)
    {
        $this->userId = $userId;
        $this->totalSubBatches = $totalSubBatches;
        $this->index = $index;
    }

    public function __invoke(Batch $batch)
    {
        Log::info("[CustomsDataBatch] 🧩 Sub-batch {$this->index} finished");

        if ($this->index === $this->totalSubBatches) {
            Log::info("[CustomsDataBatch] 🎉 All sub-batches completed for user {$this->userId}");

            $user = \App\Models\User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('CustomsData Processed')
                    ->body("All sub-batches have been processed.")
                    ->sendToDatabase([$user]);
            }
        }
    }
}
