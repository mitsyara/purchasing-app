<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppDown extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:down';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put the app into maintenance mode but keep home page accessible';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('down', [
            // exclude routes
            '--except' => ['/'],

            // whitelist IP
            // '--allow' => ['127.0.0.1'],

            // error view
            // '--render' => 'errors::503',
        ]);

        $this->info('App is in maintenance mode, Homepage still accessible.');
    }
}
