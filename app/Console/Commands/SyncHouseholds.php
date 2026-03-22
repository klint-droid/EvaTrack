<?php

namespace App\Console\Commands;

use App\Services\ExternalApiService;
use Illuminate\Console\Command;

class SyncHouseholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-households';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync households from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(ExternalApiService::class)->syncHouseholds();
        $this->info('Automatically synced households');
    }
}
