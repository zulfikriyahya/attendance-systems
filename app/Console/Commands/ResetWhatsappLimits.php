<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ResetWhatsappLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:reset-limits 
                            {--type= : Reset specific type (presensi, bulk, informasi, all)}
                            {--scope= : Reset scope (minute, hour, day, circuit, all)}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset WhatsApp rate limit counters (use with caution!)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'all';
        $scope = $this->option('scope') ?? 'all';
        $force = $this->option('force');

        if (! $force) {
            if (! $this->confirm('⚠️  Are you sure you want to reset rate limit counters? This may cause temporary rate limit issues.')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        $now = now();
        $resetCount = 0;

        $this->info('🔄 Resetting WhatsApp rate limits...');
        $this->newLine();

        // Reset based on scope
        if ($scope === 'all' || $scope === 'circuit') {
            $this->resetCircuitBreaker();
            $resetCount++;
        }

        if ($scope === 'all' || $scope === 'minute') {
            $resetCount += $this->resetMinuteLimits($type, $now);
        }

        if ($scope === 'all' || $scope === 'hour') {
            $resetCount += $this->resetHourlyLimits($type, $now);
        }

        if ($scope === 'all' || $scope === 'day') {
            $resetCount += $this->resetDailyLimits($type, $now);
        }

        $this->newLine();
        $this->info("✅ Reset complete! {$resetCount} counters cleared.");
        $this->warn('⚠️  Note: New limits will take effect immediately.');

        return 0;
    }

    protected function resetCircuitBreaker(): void
    {
        $now = now();

        Cache::forget('whatsapp_circuit_breaker_open');
        Cache::forget('whatsapp_errors_'.$now->format('Y-m-d-H'));

        $this->line('✓ Circuit breaker reset');
    }

    protected function resetMinuteLimits(string $type, $now): int
    {
        $count = 0;
        $types = $type === 'all' ? ['presensi', 'bulk', 'informasi'] : [$type];

        foreach ($types as $t) {
            $key = "whatsapp_rate_limit_{$t}_".$now->format('Y-m-d-H-i');
            Cache::forget($key);
            $count++;
            $this->line("✓ Minute limit reset: {$t}");
        }

        return $count;
    }

    protected function resetHourlyLimits(string $type, $now): int
    {
        $count = 0;

        // Global hourly
        Cache::forget('whatsapp_global_hourly_'.$now->format('Y-m-d-H'));
        $this->line('✓ Global hourly limit reset');
        $count++;

        // Type-specific hourly
        $types = $type === 'all' ? ['presensi', 'bulk', 'informasi'] : [$type];
        foreach ($types as $t) {
            $key = "whatsapp_{$t}_hourly_".$now->format('Y-m-d-H');
            Cache::forget($key);
            $count++;
            $this->line("✓ Hourly limit reset: {$t}");
        }

        return $count;
    }

    protected function resetDailyLimits(string $type, $now): int
    {
        $count = 0;

        // Global daily
        Cache::forget('whatsapp_global_daily_'.$now->format('Y-m-d'));
        $this->line('✓ Global daily limit reset');
        $count++;

        // Type-specific daily
        $types = $type === 'all' ? ['presensi', 'bulk', 'informasi'] : [$type];
        foreach ($types as $t) {
            $key = "whatsapp_{$t}_daily_".$now->format('Y-m-d');
            Cache::forget($key);
            $count++;
            $this->line("✓ Daily limit reset: {$t}");
        }

        return $count;
    }
}
