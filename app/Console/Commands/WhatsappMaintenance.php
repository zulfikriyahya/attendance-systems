<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use App\Services\WhatsappMonitorService;

class WhatsappMaintenance extends Command
{
    protected $signature = 'whatsapp:maintenance 
                          {action : Action to perform (clear-failed|retry-failed|clear-cache|health-check|stats)}
                          {--hours=24 : Hours to look back for stats}
                          {--limit=100 : Limit for batch operations}
                          {--force : Force operation without confirmation}';

    protected $description = 'WhatsApp queue maintenance operations';

    protected WhatsappMonitorService $monitorService;

    public function __construct(WhatsappMonitorService $monitorService)
    {
        parent::__construct();
        $this->monitorService = $monitorService;
    }

    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'clear-failed' => $this->clearFailedJobs(),
            'retry-failed' => $this->retryFailedJobs(),
            'clear-cache' => $this->clearCache(),
            'health-check' => $this->healthCheck(),
            'stats' => $this->displayStats(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    protected function clearFailedJobs(): int
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            $this->info('No failed jobs to clear.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Clear {$count} failed jobs?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $cleared = DB::table('failed_jobs')->delete();
        $this->info("✅ Cleared {$cleared} failed jobs.");

        return 0;
    }

    protected function retryFailedJobs(): int
    {
        $failedJobs = DB::table('failed_jobs')->limit($this->option('limit'))->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs to retry.');
            return 0;
        }

        $count = $failedJobs->count();

        if (!$this->option('force') && !$this->confirm("Retry {$count} failed jobs?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info("Retrying {$count} failed jobs...");

        $progressBar = $this->output->createProgressBar($count);
        $retried = 0;

        foreach ($failedJobs as $failedJob) {
            try {
                // Decode the payload and re-queue the job
                $payload = json_decode($failedJob->payload, true);

                if (isset($payload['data']['command'])) {
                    $command = unserialize($payload['data']['command']);
                    dispatch($command);

                    // Remove the failed job after successful retry
                    DB::table('failed_jobs')->where('id', $failedJob->id)->delete();
                    $retried++;
                }
            } catch (\Exception $e) {
                $this->warn("Failed to retry job {$failedJob->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Successfully retried {$retried} jobs.");

        return 0;
    }

    protected function clearCache(): int
    {
        $pattern = $this->ask('Enter cache pattern (leave empty for all WhatsApp caches)', '');

        if (!$this->option('force') && !$this->confirm('Clear cache counters?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $cleared = $this->monitorService->clearCacheCounters($pattern ?: null);
        $this->info("✅ Cleared {$cleared} cache entries.");

        return 0;
    }

    protected function healthCheck(): int
    {
        $this->info('🏥 WhatsApp System Health Check');
        $this->line('================================');
        $this->newLine();

        // Get health status
        $health = $this->monitorService->getHealthStatus();
        $stats = $this->monitorService->getQueueStats();
        $rateLimits = $this->monitorService->getRateLimitStats();

        // Overall Status
        $statusIcon = match ($health['overall_status']) {
            'good' => '✅',
            'warning' => '⚠️',
            'critical' => '❌',
            default => '❓'
        };

        $this->line("Overall Status: {$statusIcon} " . ucfirst($health['overall_status']));
        $this->newLine();

        // Queue Health
        $this->info('📊 Queue Health');
        $this->line("Pending Jobs: {$stats['pending_jobs']}");
        $this->line("Failed Jobs: {$stats['failed_jobs']}");
        $this->line("Processing Jobs: {$stats['processing_jobs']}");

        if (!empty($stats['job_distribution'])) {
            $this->line('Job Distribution:');
            foreach ($stats['job_distribution'] as $type => $count) {
                $this->line("  - {$type}: {$count}");
            }
        }
        $this->newLine();

        // Rate Limit Health
        $this->info('⚡ Rate Limit Status');
        $currentStats = $rateLimits['current_hour_stats'];
        $total = array_sum($currentStats);
        $this->line("Current Hour Total: {$total}");

        foreach ($currentStats as $type => $count) {
            if ($count > 0) {
                $this->line("  - {$type}: {$count}");
            }
        }
        $this->newLine();

        // Processing Time Estimation
        $estimation = $rateLimits['estimated_processing_time'];
        if ($estimation['pending_jobs'] > 0) {
            $this->info('⏱️ Processing Estimation');
            $this->line("Estimated completion: {$estimation['estimated_completion']}");
            $this->line("Minutes remaining: {$estimation['estimated_minutes']}");
            $this->newLine();
        }

        // Active Alerts
        if (!empty($health['alerts'])) {
            $this->info('🚨 Active Alerts');
            foreach ($health['alerts'] as $alert) {
                $icon = $alert['type'] === 'critical' ? '❌' : '⚠️';
                $this->line("{$icon} {$alert['message']}");
            }
            $this->newLine();
        }

        // Recommendations
        $recommendations = $this->monitorService->getRecommendations();
        if (!empty($recommendations)) {
            $this->info('💡 Recommendations');
            foreach ($recommendations as $rec) {
                $icon = match ($rec['type']) {
                    'critical' => '❌',
                    'warning' => '⚠️',
                    'info' => 'ℹ️',
                    default => '•'
                };
                $this->line("{$icon} {$rec['message']}");
                $this->line("   Action: {$rec['action']}");
            }
        }

        return 0;
    }

    protected function displayStats(): int
    {
        $hours = (int) $this->option('hours');

        $this->info("📈 WhatsApp Statistics (Last {$hours} hours)");
        $this->line('=========================================');
        $this->newLine();

        // Recent Activity
        $activity = $this->monitorService->getRecentActivity($hours);
        $this->info('Recent Activity Breakdown');
        $this->line('-------------------------');

        foreach ($activity['hourly_breakdown'] as $hourData) {
            $this->line("{$hourData['datetime']}: {$hourData['total']} messages");
            if ($hourData['total'] > 0) {
                foreach ($hourData['hour_stats'] as $type => $count) {
                    if ($count > 0) {
                        $this->line("  - {$type}: {$count}");
                    }
                }
            }
        }

        $this->newLine();
        $this->line("Total Messages: {$activity['total_messages']}");
        $this->line("Average per Hour: {$activity['average_per_hour']}");
        $this->newLine();

        // Performance Metrics
        $performance = $this->monitorService->getPerformanceMetrics();
        $this->info('Performance Metrics');
        $this->line('-------------------');

        if ($performance['success_rate'] !== null) {
            $this->line("Success Rate: {$performance['success_rate']}%");
        }

        if (!empty($performance['throughput'])) {
            $this->line("Throughput (last 3h): {$performance['throughput']['last_3_hours_total']} messages");
            $this->line("Average per hour: {$performance['throughput']['average_per_hour']}");
        }

        if (!empty($performance['peak_hours'])) {
            $this->line("Peak Hours:");
            foreach ($performance['peak_hours'] as $period => $hours) {
                $this->line("  - {$period}: " . implode(', ', $hours));
            }
        }

        $this->newLine();

        // Daily Summary
        $today = now()->format('Y-m-d');
        $dailyStats = $this->monitorService->getRateLimitStats()['daily_stats'];
        $dailyTotal = array_sum($dailyStats);

        $this->info('Today\'s Summary');
        $this->line('---------------');
        $this->line("Total messages today: {$dailyTotal}");

        foreach ($dailyStats as $type => $count) {
            if ($count > 0) {
                $percentage = $dailyTotal > 0 ? round(($count / $dailyTotal) * 100, 1) : 0;
                $this->line("  - {$type}: {$count} ({$percentage}%)");
            }
        }

        return 0;
    }
}

// Additional Command for Queue Worker Management
class WhatsappWorkerManager extends Command
{
    protected $signature = 'whatsapp:worker 
                          {action : Action (start|stop|restart|status)}
                          {--workers=3 : Number of workers}
                          {--timeout=300 : Worker timeout in seconds}
                          {--memory=512 : Memory limit in MB}';

    protected $description = 'Manage WhatsApp queue workers';

    public function handle()
    {
        $action = $this->argument('action');

        return match ($action) {
            'start' => $this->startWorkers(),
            'stop' => $this->stopWorkers(),
            'restart' => $this->restartWorkers(),
            'status' => $this->workerStatus(),
            default => $this->error("Unknown action: {$action}")
        };
    }

    protected function startWorkers(): int
    {
        $workers = $this->option('workers');
        $timeout = $this->option('timeout');
        $memory = $this->option('memory');

        $this->info("Starting {$workers} WhatsApp queue workers...");

        for ($i = 1; $i <= $workers; $i++) {
            $command = "php artisan queue:work --queue=default --timeout={$timeout} --memory={$memory} --daemon > /dev/null 2>&1 &";
            exec($command);
            $this->line("Started worker {$i}");
        }

        $this->info("✅ Successfully started {$workers} workers");
        return 0;
    }

    protected function stopWorkers(): int
    {
        $this->info("Stopping WhatsApp queue workers...");

        // Send SIGTERM to all queue:work processes
        exec("pkill -f 'queue:work'", $output, $returnCode);

        if ($returnCode === 0) {
            $this->info("✅ Workers stop signal sent");
        } else {
            $this->warn("⚠️  No running workers found or failed to stop");
        }

        return 0;
    }

    protected function restartWorkers(): int
    {
        $this->info("Restarting WhatsApp queue workers...");

        // Stop existing workers
        $this->call('queue:restart');
        sleep(2);

        // Start new workers
        return $this->startWorkers();
    }

    protected function workerStatus(): int
    {
        $this->info('WhatsApp Queue Worker Status');
        $this->line('============================');

        // Check running processes
        exec("ps aux | grep 'queue:work' | grep -v grep", $processes);

        if (empty($processes)) {
            $this->warn('⚠️  No queue workers are currently running');
        } else {
            $this->info("✅ Found " . count($processes) . " running workers:");
            foreach ($processes as $process) {
                // Extract relevant info from process list
                $parts = preg_split('/\s+/', $process);
                $pid = $parts[1] ?? 'unknown';
                $cpu = $parts[2] ?? 'unknown';
                $memory = $parts[3] ?? 'unknown';

                $this->line("  PID: {$pid} | CPU: {$cpu}% | Memory: {$memory}%");
            }
        }

        $this->newLine();

        // Queue status
        $pendingJobs = Queue::size('default');
        $this->line("Pending jobs in queue: {$pendingJobs}");

        return 0;
    }
}
