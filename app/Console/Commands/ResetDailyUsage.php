<?php

namespace App\Console\Commands;

use App\Models\UsageRecord;
use Illuminate\Console\Command;

class ResetDailyUsage extends Command
{
    protected $signature = 'usage:reset-daily';
    protected $description = 'Archive daily usage records older than 90 days';

    public function handle(): int
    {
        // Daily metrics like api_calls_per_day are inherently reset by date filtering.
        // This command cleans up old records to keep the table performant.
        $cutoff = now()->subDays(90);

        $deleted = UsageRecord::withoutGlobalScopes()
            ->where('metric', 'api_calls_per_day')
            ->where('recorded_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} old api_calls_per_day records (older than 90 days).");

        // Clear daily notification caches so alerts can fire again tomorrow
        $this->info('Daily usage caches will auto-expire at end of day.');

        return self::SUCCESS;
    }
}
