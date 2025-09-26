<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CustomsDataCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCustomsDataAggregatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public ?int $timeout = 600; // 10 phút

    /**
     * Handle the queued job.
     */
    public function handle(): void
    {
        Log::info('[RecalculateCustomsDataAggregates] Job started.');

        try {
            $counts = \App\Models\CustomsData::on('mysql_customs_data')
                ->select('customs_data_category_id', DB::raw('count(*) as total'))
                ->groupBy('customs_data_category_id')
                ->pluck('total', 'customs_data_category_id');

            Log::info('[RecalculateCustomsDataAggregates] Counts fetched.', ['counts' => $counts->toArray()]);

            DB::connection('mysql_customs_data')->transaction(function () use ($counts) {
                foreach ($counts as $categoryId => $total) {
                    DB::connection('mysql_customs_data')->table('customs_data_categories')
                        ->where('id', $categoryId)
                        ->update(['count' => $total]);

                    Cache::put("category:{$categoryId}", $total, now()->addDay());
                }

                $allCategoryIds = \App\Models\CustomsDataCategory::on('mysql_customs_data')->pluck('id');
                $missingIds = $allCategoryIds->diff($counts->keys());

                foreach ($missingIds as $id) {
                    DB::connection('mysql_customs_data')->table('customs_data_categories')
                        ->where('id', $id)
                        ->update(['count' => 0]);
                    Cache::put("category:{$id}", 0, now()->addDay());
                }
            });

            Log::info('[RecalculateCustomsDataAggregates] Job completed successfully.');
        } catch (\Throwable $e) {
            Log::error('[RecalculateCustomsDataAggregates] Job failed.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
