<?php

namespace App\Console\Commands\CustomsData;

use App\Models\CustomsData;
use App\Models\CustomsDataCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupCategoryCommand extends Command
{
    protected $signature = 'cus-data:category-cleanup 
                            {--dry-run : Chỉ hiển thị kết quả không thực thi}
                            {--reset-hash : Reset hash về null cho tất cả records}
                            {--reset-category : Reset category về null cho tất cả records}
                            {--fix-orphans : Sửa các records có category_id không tồn tại}';

    protected $description = 'Dọn dẹp dữ liệu CustomsData Category';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $resetHash = $this->option('reset-hash');
        $resetCategory = $this->option('reset-category');
        $fixOrphans = $this->option('fix-orphans');

        if ($dryRun) {
            $this->warn("🔍 DRY RUN MODE - Không có thay đổi nào được thực hiện");
        }

        $this->info("🧹 Bắt đầu dọn dẹp CustomsData Category");

        if ($resetHash) {
            $this->resetCategoryKeywordsHash($dryRun);
        }

        if ($resetCategory) {
            $this->resetCategoryIds($dryRun);
        }

        if ($fixOrphans) {
            $this->fixOrphanedRecords($dryRun);
        }

        if (!$resetHash && !$resetCategory && !$fixOrphans) {
            $this->displayCleanupStats();
        }

        $this->info("✅ Dọn dẹp hoàn tất");

        return self::SUCCESS;
    }

    /**
     * Reset category_keywords_hash về null
     */
    protected function resetCategoryKeywordsHash(bool $dryRun): void
    {
        $count = CustomsData::on('mysql_customs_data')->whereNotNull('category_keywords_hash')->count();
        
        $this->info("🔄 Reset category_keywords_hash cho {$count} records");

        if (!$dryRun) {
            if ($this->confirm("Bạn có chắc chắn muốn reset hash cho {$count} records?")) {
                CustomsData::on('mysql_customs_data')->whereNotNull('category_keywords_hash')
                    ->update(['category_keywords_hash' => null]);
                $this->info("✅ Đã reset hash cho {$count} records");
            } else {
                $this->info("❌ Hủy bỏ reset hash");
            }
        }
    }

    /**
     * Reset customs_data_category_id về null
     */
    protected function resetCategoryIds(bool $dryRun): void
    {
        $count = CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')->count();
        
        $this->info("🔄 Reset customs_data_category_id cho {$count} records");

        if (!$dryRun) {
            if ($this->confirm("Bạn có chắc chắn muốn reset category cho {$count} records?")) {
                CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')
                    ->update([
                        'customs_data_category_id' => null,
                        'category_keywords_hash' => null
                    ]);
                $this->info("✅ Đã reset category cho {$count} records");
            } else {
                $this->info("❌ Hủy bỏ reset category");
            }
        }
    }

    /**
     * Sửa các records có category_id không tồn tại
     */
    protected function fixOrphanedRecords(bool $dryRun): void
    {
        $validCategoryIds = CustomsDataCategory::pluck('id')->toArray();
        
        $orphanedRecords = CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')
            ->whereNotIn('customs_data_category_id', $validCategoryIds)
            ->count();

        $this->info("🔧 Tìm thấy {$orphanedRecords} records có category_id không hợp lệ");

        if ($orphanedRecords > 0 && !$dryRun) {
            if ($this->confirm("Bạn có muốn reset category cho {$orphanedRecords} records này?")) {
                CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')
                    ->whereNotIn('customs_data_category_id', $validCategoryIds)
                    ->update([
                        'customs_data_category_id' => null,
                        'category_keywords_hash' => null
                    ]);
                $this->info("✅ Đã sửa {$orphanedRecords} records");
            } else {
                $this->info("❌ Hủy bỏ sửa orphaned records");
            }
        }
    }

    /**
     * Hiển thị thống kê cleanup
     */
    protected function displayCleanupStats(): void
    {
        $currentHash = CustomsDataCategory::currentKeywordsHash();
        $validCategoryIds = CustomsDataCategory::pluck('id')->toArray();

        $stats = [
            'total_records' => CustomsData::on('mysql_customs_data')->count(),
            'with_category' => CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')->count(),
            'with_hash' => CustomsData::on('mysql_customs_data')->whereNotNull('category_keywords_hash')->count(),
            'with_current_hash' => CustomsData::on('mysql_customs_data')->where('category_keywords_hash', $currentHash)->count(),
            'with_old_hash' => CustomsData::on('mysql_customs_data')->whereNotNull('category_keywords_hash')
                ->where('category_keywords_hash', '!=', $currentHash)->count(),
            'orphaned_category' => CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')
                ->whereNotIn('customs_data_category_id', $validCategoryIds)->count(),
            'inconsistent' => CustomsData::on('mysql_customs_data')->whereNotNull('customs_data_category_id')
                ->whereNull('category_keywords_hash')->count(),
        ];

        $this->info("📊 THỐNG KÊ CLEANUP");
        $this->info("══════════════════════");
        $this->info("📈 Tổng records: " . number_format($stats['total_records']));
        $this->info("🏷️ Có category: " . number_format($stats['with_category']));
        $this->info("🔑 Có hash: " . number_format($stats['with_hash']));
        $this->info("✅ Hash hiện tại: " . number_format($stats['with_current_hash']));
        $this->info("🔄 Hash cũ: " . number_format($stats['with_old_hash']));
        $this->info("🚨 Category không hợp lệ: " . number_format($stats['orphaned_category']));
        $this->info("⚠️ Không nhất quán (có category nhưng không có hash): " . number_format($stats['inconsistent']));

        if ($stats['with_old_hash'] > 0) {
            $this->warn("⚠️ Có {$stats['with_old_hash']} records với hash cũ cần xử lý lại");
        }

        if ($stats['orphaned_category'] > 0) {
            $this->error("❌ Có {$stats['orphaned_category']} records với category không hợp lệ");
        }

        if ($stats['inconsistent'] > 0) {
            $this->warn("⚠️ Có {$stats['inconsistent']} records không nhất quán");
        }

        $this->info("\n💡 Sử dụng các option để dọn dẹp:");
        $this->info("   --reset-hash : Reset tất cả hash");
        $this->info("   --reset-category : Reset tất cả category");  
        $this->info("   --fix-orphans : Sửa category không hợp lệ");
    }
}
