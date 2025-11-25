<?php

namespace App\Console\Commands\CustomsData;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process as SymfonyProcess;

class ProcessCategoryAutoCommand extends Command
{
    protected $signature = 'cus-data:category-auto 
                            {--processes=3 : Số lượng process song song (chỉ dùng khi multi-process available)}
                            {--chunk-size=500 : Kích thước chunk}
                            {--max= : Tối đa số records cần xử lý (ví dụ: 500000)}
                            {--force : Buộc xử lý lại tất cả records}
                            {--stats : Hiển thị performance stats chi tiết}';

    protected $description = 'Auto-detect và chạy version phù hợp (multi-process hoặc single-threaded)';

    public function handle(): int
    {
        // Check if multi-processing is available
        if ($this->canUseMultiProcessing()) {
            return $this->runMultiProcess();
        } else {
            $this->warn("⚠️ Multi-processing not available - Using single-threaded fallback");
            return $this->runSingleThreaded();
        }
    }

    /**
     * Kiểm tra xem có thể sử dụng multi-processing không
     */
    protected function canUseMultiProcessing(): bool
    {
        // Check if required functions are available
        if (!function_exists('proc_open') || !function_exists('proc_close')) {
            $this->warn("❌ proc_open/proc_close functions disabled");
            return false;
        }

        // Check if we can create a simple process
        try {
            $process = new SymfonyProcess(['php', '--version']);
            $process->setTimeout(5);
            $process->run();
            
            if ($process->isSuccessful() && !empty($process->getOutput())) {
                // Test a simple artisan command to verify process creation works
                $testProcess = new SymfonyProcess([
                    'php', 
                    base_path('artisan'), 
                    'list', 
                    '--format=json'
                ]);
                $testProcess->setTimeout(10);
                $testProcess->run();
                
                return $testProcess->isSuccessful();
            }
            
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Chạy version multi-process
     */
    protected function runMultiProcess(): int
    {
        $options = [];
        
        if ($this->option('processes')) {
            $options['--processes'] = $this->option('processes');
        }
        
        if ($this->option('chunk-size')) {
            $options['--chunk-size'] = $this->option('chunk-size');
        }
        
        if ($this->option('max')) {
            $options['--max'] = $this->option('max');
        }
        
        if ($this->option('force')) {
            $options['--force'] = true;
        }
        
        if ($this->option('stats')) {
            $options['--stats'] = true;
        }

        return $this->call('cus-data:category', $options);
    }

    /**
     * Chạy version single-threaded
     */
    protected function runSingleThreaded(): int
    {
        $options = [];
        
        if ($this->option('chunk-size')) {
            $options['--chunk-size'] = $this->option('chunk-size');
        }
        
        if ($this->option('max')) {
            $options['--max'] = $this->option('max');
        }
        
        if ($this->option('force')) {
            $options['--force'] = true;
        }
        
        if ($this->option('stats')) {
            $options['--stats'] = true;
        }

        return $this->call('cus-data:category-single', $options);
    }
}