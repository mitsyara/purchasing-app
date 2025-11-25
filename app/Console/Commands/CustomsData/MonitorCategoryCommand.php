<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorCategoryCommand extends Command
{
    protected $signature = 'cus-data:category-monitor 
                            {--refresh=5 : Thời gian refresh (giây)}
                            {--once : Chỉ hiển thị một lần không refresh}';

    protected $description = 'Theo dõi tiến trình xử lý CustomsData Category';

    public function handle(): int
    {
        $refresh = (int) $this->option('refresh');
        $once = $this->option('once');

        do {
            $this->displayStats();
            
            if ($once) {
                break;
            }

            $this->info("⏱️ Refresh sau {$refresh} giây... (Ctrl+C để thoát)");
            sleep($refresh);
            
            // Clear console for clean display
            $this->output->write("\033[2J\033[;H");
            
        } while (true);

        return self::SUCCESS;
    }

    protected function displayStats(): void
    {
        $currentHash = CustomsDataCategory::currentKeywordsHash();
        
        // Tổng số records
        $totalRecords = CustomsData::on('mysql_customs_data')->count();
        
        // Records đã có category
        $withCategory = CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')->count();
        
        // Records đã xử lý với hash hiện tại
        $processedWithCurrentHash = CustomsData::on('mysql_customs_data')->where('category_keywords_hash', $currentHash)->count();
        
        // Records cần xử lý
        $needProcessing = CustomsData::on('mysql_customs_data')->where(function ($q) use ($currentHash) {
            $q->whereNull('customs_data_category_id')
              ->orWhere('category_keywords_hash', '!=', $currentHash)
              ->orWhereNull('category_keywords_hash');
        })->count();

        // Records được assign category với hash hiện tại
        $assignedWithCurrentHash = CustomsData::on('mysql_customs_data')->where('category_keywords_hash', $currentHash)
            ->whereNotNull('customs_data_category_id')
            ->count();

        // Phần trăm hoàn thành
        $completionPercent = $totalRecords > 0 ? round(($processedWithCurrentHash / $totalRecords) * 100, 2) : 0;
        
        // Category distribution
        $categoryStats = DB::connection('mysql_customs_data')->table('customs_data as cd')
            ->leftJoin('customs_data_categories as cdc', 'cd.customs_data_category_id', '=', 'cdc.id')
            ->select(
                DB::raw('COALESCE(cdc.name, "Chưa phân loại") as category_name'),
                DB::raw('COUNT(*) as count')
            )
            ->where('cd.category_keywords_hash', $currentHash)
            ->groupBy('cdc.id', 'cdc.name')
            ->orderByDesc('count')
            ->get();

        // Display
        $this->info("📊 TRẠNG THÁI XỬ LÝ CUSTOMS DATA CATEGORY");
        $this->info("═══════════════════════════════════════════════");
        $this->info("🔑 Keywords Hash: {$currentHash}");
        $this->info("📈 Tổng số records: " . number_format($totalRecords));
        $this->info("✅ Đã xử lý (hash hiện tại): " . number_format($processedWithCurrentHash) . " ({$completionPercent}%)");
        $this->info("🎯 Đã có category: " . number_format($withCategory));
        $this->info("🏷️ Assigned với hash hiện tại: " . number_format($assignedWithCurrentHash));
        $this->info("⏳ Cần xử lý: " . number_format($needProcessing));
        
        // Progress bar
        if ($totalRecords > 0) {
            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->setProgress($processedWithCurrentHash);
            $this->newLine();
            $progressBar->display();
            $this->newLine(2);
        }

        // Category distribution
        $this->info("📋 PHÂN PHỐI THEO CATEGORY:");
        $this->info("────────────────────────────");
        
        $this->table(
            ['Category', 'Số lượng', 'Phần trăm'],
            $categoryStats->map(function ($stat) use ($processedWithCurrentHash) {
                $percent = $processedWithCurrentHash > 0 ? round(($stat->count / $processedWithCurrentHash) * 100, 2) : 0;
                return [
                    $stat->category_name,
                    number_format($stat->count),
                    $percent . '%'
                ];
            })->take(10)->toArray() // Chỉ hiển thị top 10
        );

        if ($categoryStats->count() > 10) {
            $this->info("... và " . ($categoryStats->count() - 10) . " category khác");
        }

        $this->info("\n⏰ Cập nhật lúc: " . now()->format('Y-m-d H:i:s'));
    }
}
