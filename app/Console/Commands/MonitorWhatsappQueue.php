<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class MonitorWhatsappQueue extends Command
{
    protected $signature = 'whatsapp:monitor {--clear-cache : Clear hourly cache counters}';

    protected $description = 'Monitor WhatsApp queue status and statistics';

    public function handle()
    {
        if ($this->option('clear-cache')) {
            $this->clearCacheCounters();

            return 0;
        }

        $this->displayHeader();
        $this->displayQueueStats();
        $this->displayRateLimitStats();
        $this->displayRecentActivity();
        $this->displayHealthChecks();
        $this->displayRecommendations();

        return 0;
    }

    protected function displayHeader(): void
    {
        $this->info('WhatsApp Queue Monitoring Dashboard');
        $this->info('====================================');
        $this->line('Current Time: '.now()->format('Y-m-d H:i:s'));
        $this->newLine();
    }

    protected function displayQueueStats(): void
    {
        $this->info('📊 Queue Statistics');
        $this->line('-------------------');

        // Basic queue stats
        $pendingJobs = Queue::size('default');
        $failedJobs = DB::table('failed_jobs')->count();

        $this->line("Pending Jobs: <comment>{$pendingJobs}</comment>");
        $this->line("Failed Jobs: <comment>{$failedJobs}</comment>");

        // Job distribution by type (if available)
        $this->displayJobDistribution();

        $this->newLine();
    }

    protected function displayJobDistribution(): void
    {
        try {
            // Get job distribution from jobs table (if exists)
            $jobs = DB::table('jobs')
                ->select('payload')
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $className = $payload['displayName'] ?? 'Unknown';

                    return basename(str_replace('\\', '/', $className));
                })
                ->countBy()
                ->toArray();

            if (! empty($jobs)) {
                $this->line('Job Types:');
                foreach ($jobs as $type => $count) {
                    $this->line("  - {$type}: {$count}");
                }
            }
        } catch (\Exception $e) {
            // Silently ignore if jobs table doesn't exist or other errors
        }
    }

    protected function displayRateLimitStats(): void
    {
        $this->info('⚡ Rate Limit Statistics');
        $this->line('------------------------');

        $today = now()->format('Y-m-d');
        $currentHour = now()->format('H');

        // Current hour stats
        $presensiKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $presensiCount = Cache::get($presensiKey, 0);

        $this->line("Presensi notifications this hour: <comment>{$presensiCount}</comment>");

        // Check if there are other cache keys (bulk operations)
        $bulkKeys = $this->getBulkCacheKeys($today, $currentHour);
        foreach ($bulkKeys as $key => $count) {
            $this->line("{$key}: <comment>{$count}</comment>");
        }

        // Rate limits
        $presensiLimit = 35; // From your WhatsappDelayService
        $bulkLimit = 20;     // From your WhatsappDelayService

        $this->line('Rate limits:');
        $this->line("  - Presensi: {$presensiLimit} messages/minute");
        $this->line("  - Bulk Operations: {$bulkLimit} messages/minute");

        // Calculate estimated processing time
        $this->displayProcessingEstimate($presensiCount, $presensiLimit);

        $this->newLine();
    }

    protected function getBulkCacheKeys(string $today, string $currentHour): array
    {
        $bulkKeys = [];

        // Try to get cache keys for different bulk operations
        $possibleKeys = [
            'alfa_notifications' => "whatsapp_bulk_alfa_{$today}_{$currentHour}",
            'mangkir_notifications' => "whatsapp_bulk_mangkir_{$today}_{$currentHour}",
            'bolos_notifications' => "whatsapp_bulk_bolos_{$today}_{$currentHour}",
            'informasi_notifications' => "whatsapp_informasi_{$today}_{$currentHour}",
        ];

        foreach ($possibleKeys as $label => $key) {
            $count = Cache::get($key, 0);
            if ($count > 0) {
                $bulkKeys[$label] = $count;
            }
        }

        return $bulkKeys;
    }

    protected function displayProcessingEstimate(int $currentCount, int $rateLimit): void
    {
        if ($currentCount > 0) {
            $estimatedMinutes = ceil($currentCount / $rateLimit);
            $this->line("Estimated processing time: <comment>{$estimatedMinutes} minutes</comment>");
        }
    }

    protected function displayRecentActivity(): void
    {
        $this->info('📈 Recent Activity (Last 3 hours)');
        $this->line('----------------------------------');

        $today = now()->format('Y-m-d');
        $currentHour = (int) now()->format('H');

        $totalLast3Hours = 0;
        $hourlyBreakdown = [];

        for ($i = 2; $i >= 0; $i--) {
            $hour = $currentHour - $i;
            if ($hour < 0) {
                $hour += 24;
                $date = now()->subDay()->format('Y-m-d');
            } else {
                $date = $today;
            }

            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $key = "whatsapp_hourly_{$date}_{$hourStr}";
            $count = Cache::get($key, 0);

            $totalLast3Hours += $count;
            $hourlyBreakdown[] = sprintf('%s:%s - %d messages', $hourStr, '00', $count);
        }

        foreach ($hourlyBreakdown as $breakdown) {
            $this->line("  {$breakdown}");
        }

        $this->line("Total last 3 hours: <comment>{$totalLast3Hours}</comment>");
        $this->newLine();
    }

    protected function displayHealthChecks(): void
    {
        $this->info('🏥 Health Checks');
        $this->line('----------------');

        $pendingJobs = Queue::size('default');
        $failedJobs = DB::table('failed_jobs')->count();

        // Queue health
        if ($pendingJobs > 10000) {
            $this->error("❌ Critical: Very high queue volume ({$pendingJobs} jobs)");
        } elseif ($pendingJobs > 5000) {
            $this->warn("⚠️  Warning: High queue volume ({$pendingJobs} jobs)");
        } elseif ($pendingJobs > 1000) {
            $this->line("⚡ Info: Moderate queue volume ({$pendingJobs} jobs)");
        } else {
            $this->info("✅ Good: Queue volume is normal ({$pendingJobs} jobs)");
        }

        // Failed jobs health
        if ($failedJobs > 100) {
            $this->error("❌ Critical: High failed job count ({$failedJobs})");
        } elseif ($failedJobs > 20) {
            $this->warn("⚠️  Warning: Moderate failed job count ({$failedJobs})");
        } else {
            $this->info("✅ Good: Failed job count is acceptable ({$failedJobs})");
        }

        // Rate limit health
        $today = now()->format('Y-m-d');
        $currentHour = now()->format('H');
        $currentHourCount = Cache::get("whatsapp_hourly_{$today}_{$currentHour}", 0);

        if ($currentHourCount > 2000) {
            $this->warn("⚠️  Warning: High message volume this hour ({$currentHourCount})");
        } else {
            $this->info("✅ Good: Message volume is normal this hour ({$currentHourCount})");
        }

        $this->newLine();
    }

    protected function displayRecommendations(): void
    {
        $this->info('💡 Recommendations');
        $this->line('-------------------');

        $pendingJobs = Queue::size('default');
        $failedJobs = DB::table('failed_jobs')->count();

        $recommendations = [];

        if ($pendingJobs > 5000) {
            $recommendations[] = 'Consider scaling up queue workers';
            $recommendations[] = 'Check if WhatsApp service is responding properly';
        }

        if ($failedJobs > 20) {
            $recommendations[] = 'Review failed jobs: php artisan queue:failed';
            $recommendations[] = 'Consider retrying failed jobs: php artisan queue:retry all';
        }

        $currentHourCount = Cache::get('whatsapp_hourly_'.now()->format('Y-m-d_H'), 0);
        if ($currentHourCount > 1500) {
            $recommendations[] = 'Monitor WhatsApp API rate limits';
            $recommendations[] = 'Consider implementing circuit breaker pattern';
        }

        if (empty($recommendations)) {
            $this->info('✅ No specific recommendations at this time');
        } else {
            foreach ($recommendations as $recommendation) {
                $this->line("• {$recommendation}");
            }
        }

        $this->newLine();
    }

    protected function clearCacheCounters(): void
    {
        $this->info('Clearing cache counters...');

        $today = now()->format('Y-m-d');
        $patterns = [
            "whatsapp_hourly_{$today}_*",
            "whatsapp_bulk_*_{$today}_*",
            "whatsapp_informasi_{$today}_*",
        ];

        $cleared = 0;
        foreach ($patterns as $pattern) {
            // Note: This is a simplified approach
            // In production, you might want to use Redis SCAN or similar
            for ($hour = 0; $hour < 24; $hour++) {
                $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
                $keys = [
                    "whatsapp_hourly_{$today}_{$hourStr}",
                    "whatsapp_bulk_alfa_{$today}_{$hourStr}",
                    "whatsapp_bulk_mangkir_{$today}_{$hourStr}",
                    "whatsapp_bulk_bolos_{$today}_{$hourStr}",
                    "whatsapp_informasi_{$today}_{$hourStr}",
                ];

                foreach ($keys as $key) {
                    if (Cache::forget($key)) {
                        $cleared++;
                    }
                }
            }
        }

        $this->info("✅ Cleared {$cleared} cache entries");
    }
}
