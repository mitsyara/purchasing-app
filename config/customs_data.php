<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CustomsData Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling large CustomsData datasets
    |
    */

    // ✅ Auto-detect large datasets and switch to optimized mode
    'auto_optimize_threshold' => env('CUSTOMS_AUTO_OPTIMIZE_THRESHOLD', 500000),
    
    // ✅ Force optimized mode regardless of dataset size
    'force_optimized_mode' => env('CUSTOMS_FORCE_OPTIMIZED_MODE', false),
    
    // ✅ Pagination settings for large datasets
    'large_dataset_pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
        'available_options' => [25, 50, 100],
    ],
    
    // ✅ Performance monitoring
    'performance_monitoring' => [
        'slow_query_threshold_ms' => 1000,
        'log_slow_queries' => env('CUSTOMS_LOG_SLOW_QUERIES', true),
        'enable_query_profiling' => env('CUSTOMS_ENABLE_PROFILING', false),
    ],
    
    // ✅ Feature toggles for large datasets
    'large_dataset_features' => [
        'disable_summarizers' => true,
        'use_estimated_counts' => true,
        'enable_query_hints' => true,
        'force_index_usage' => true,
    ],
    
    // ✅ Search optimization
    'search_optimization' => [
        'fulltext_min_length' => 3,
        'enable_prefix_search' => true,
        'max_search_results' => 1000,
    ],
    
    // ✅ Cache settings
    'cache' => [
        'categories_ttl' => 86400, // 24 hours
        'stats_ttl' => 3600, // 1 hour
        'enable_query_cache' => true,
    ],
];