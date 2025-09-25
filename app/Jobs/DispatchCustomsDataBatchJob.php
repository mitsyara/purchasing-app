<?php

namespace App\Jobs;

use App\Jobs\ProcessCustomsDataCategory;
use App\Models\CustomsDataCategory;
use App\Models\CustomsData;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchCustomsDataBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $keywordsHash = CustomsDataCategory::currentKeywordsHash();

        CustomsData::where(function (Builder $q) use ($keywordsHash) {
            $q->whereNull('customs_data_category_id')
                ->where(
                    fn(Builder $sq): Builder =>
                    $sq->whereNull('category_keywords_hash')
                        ->orWhere('category_keywords_hash', '!=', $keywordsHash)
                );
        })->select('id')
            ->chunkById(1000, function ($records) use ($keywordsHash) {
                $jobs = [];

                foreach ($records as $record) {
                    $jobs[] = new ProcessCustomsDataCategoryJob($record->id, $keywordsHash);
                }

                if (!empty($jobs)) {
                    Bus::batch($jobs)
                        ->then(fn(Batch $batch) => Log::info("✅ Batch {$batch->id} completed"))
                        ->catch(
                            fn(Batch $batch, Throwable $e) =>
                            Log::error("❌ Batch {$batch->id} failed: {$e->getMessage()}")
                        )
                        ->finally(fn(Batch $batch) => Log::info("ℹ️ Batch {$batch->id} finished"))
                        ->dispatch();
                }
            });
    }
}