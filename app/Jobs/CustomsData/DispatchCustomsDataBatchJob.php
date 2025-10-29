<?php

namespace App\Jobs\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchCustomsDataBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $keywordsHash = CustomsDataCategory::currentKeywordsHash();

        // Subquery no load ID to PHP
        $subQuery = CustomsData::where('category_keywords_hash', $keywordsHash)
            ->select('id');

        Log::info("[CustomsDataBatch] 🔄 Start dispatch for user {$this->user->id}", [
            'Hash' => $keywordsHash,
        ]);

        $query = CustomsData::whereNull('customs_data_category_id')
            ->whereNotIn('id', $subQuery)
            ->select('id');

        $chunkSize = 1000;
        $maxJobsPerSubBatch = 5;

        $buffer = [];
        $jobsInCurrentSubBatch = [];
        $subBatches = [];
        $totalRecords = 0;

        foreach ($query->lazyById($chunkSize) as $record) {
            $buffer[] = $record->id;

            if (count($buffer) >= $chunkSize) {
                $jobsInCurrentSubBatch[] = new ProcessBatchCustomsDataCategoryJob($buffer, $keywordsHash);
                $buffer = [];
                $totalRecords += $chunkSize;

                if (count($jobsInCurrentSubBatch) >= $maxJobsPerSubBatch) {
                    $subBatches[] = $jobsInCurrentSubBatch;
                    $jobsInCurrentSubBatch = [];
                }
            }
        }

        if (!empty($buffer)) {
            $jobsInCurrentSubBatch[] = new ProcessBatchCustomsDataCategoryJob($buffer, $keywordsHash);
            $totalRecords += count($buffer);
        }

        if (!empty($jobsInCurrentSubBatch)) {
            $subBatches[] = $jobsInCurrentSubBatch;
        }

        if (empty($subBatches)) {
            Log::info("[CustomsDataBatch] ✅ No records to process for user {$this->user->id}");
            return;
        }

        dispatch(new SequentialBatchSupervisorJob(
            $this->user->id,
            $subBatches,
            $totalRecords
        ));

        Log::info("[CustomsDataBatch] 🚀 Dispatched SequentialBatchSupervisorJob ({$totalRecords} records, " . count($subBatches) . " sub-batches)");
    }
}
