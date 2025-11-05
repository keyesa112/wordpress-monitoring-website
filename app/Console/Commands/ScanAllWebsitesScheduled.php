<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Jobs\ScanSingleWebsiteJob;
use Illuminate\Console\Command;

class ScanAllWebsitesScheduled extends Command
{
    protected $signature = 'websites:scan-all-scheduled';
    protected $description = 'Scan all websites in database (scheduled)';

    public function handle()
    {
        $this->info('Starting scheduled website scan...');

        // Ambil SEMUA website di database
        $websites = Website::all();

        if ($websites->isEmpty()) {
            $this->info('No websites to scan.');
            return;
        }

        $this->info("Found {$websites->count()} websites. Queueing for scan...");

        // Loop semua website, dispatch ke queue
        foreach ($websites as $website) {
            $website->update(['status' => 'checking']);
            
            // Pass user_id dari website
            ScanSingleWebsiteJob::dispatch($website->id, $website->user_id);
            
            $this->line("  ✓ Queued: {$website->name}");
        }

        $this->info("All {$websites->count()} websites queued for scanning!");
    }
}
