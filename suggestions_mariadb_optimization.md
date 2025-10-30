# MariaDB 10.5.29 Optimization cho CustomsDataSummary Command

## 1. Batch Processing
```php
// Thay vì xử lý toàn bộ, chia nhỏ theo batch
$batchSize = 10000;
$offset = 0;

do {
    $rows = $connection->table('customs_data')
        ->selectRaw('...')
        ->offset($offset)
        ->limit($batchSize)
        ->get();
    
    // Process batch
    $offset += $batchSize;
} while ($rows->count() === $batchSize);
```

## 2. Index Optimization
```sql
-- Tạo composite index cho performance
ALTER TABLE customs_data 
ADD INDEX idx_summary_calc (importer, customs_data_category_id, import_date, qty, value, is_vett);

-- Index cho incremental mode
ALTER TABLE customs_data_summaries 
ADD INDEX idx_import_date (import_date);
```

## 3. MariaDB Configuration
```ini
# my.cnf optimizations
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
max_allowed_packet = 64M
tmp_table_size = 256M
max_heap_table_size = 256M
```

## 4. Parallel Processing
```php
// Sử dụng Laravel Queue cho xử lý song song
dispatch(new ProcessCustomsDataSummary($dateRange));
```

## 5. Monitoring & Alerts
```php
// Thêm metrics
$startTime = microtime(true);
// ... process data ...
$duration = microtime(true) - $startTime;

Log::info("Summary generation completed", [
    'duration' => $duration,
    'records_processed' => $recordCount,
    'mode' => $force ? 'force' : 'incremental'
]);
```