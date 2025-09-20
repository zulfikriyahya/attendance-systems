<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class WhatsappMonitorService
{
    /**
     * Get comprehensive queue statistics
     */
    public function getQueueStats(): array
    {
        return [
            'pending_jobs' => Queue::size('default'),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'processing_jobs' => $this->getProcessingJobsCount(),
            'job_distribution' => $this->getJobDistribution(),
        ];
    }

    /**
     * Get rate limit statistics for current hour
     */
    public function getRateLimitStats(): array
    {
        $today = now()->format('Y-m-d');
        $currentHour = now()->format('H');

        return [
            'current_hour_stats' => $this->getCurrentHourStats($today, $currentHour),
            'daily_stats' => $this->getDailyStats($today),
            'rate_limits' => $this->getRateLimits(),
            'estimated_processing_time' => $this->getEstimatedProcessingTime(),
        ];
    }

    /**
     * Get system health status
     */
    public function getHealthStatus(): array
    {
        $stats = $this->getQueueStats();
        $rateLimits = $this->getRateLimitStats();

        $queueHealth = $this->assessQueueHealth($stats['pending_jobs'], $stats['failed_jobs']);
        $rateLimitHealth = $this->assessRateLimitHealth($rateLimits['current_hour_stats']);

        return [
            'queue_health' => $queueHealth,
            'rate_limit_health' => $rateLimitHealth,
            'overall_status' => $this->getOverallStatus(),
            'alerts' => $this->getActiveAlerts(),
        ];
    }

    /**
     * Get recent activity breakdown
     */
    public function getRecentActivity(int $hours = 6): array
    {
        $activity = [];
        $total = 0;

        for ($i = $hours - 1; $i >= 0; $i--) {
            $datetime = now()->subHours($i);
            $date = $datetime->format('Y-m-d');
            $hour = $datetime->format('H');

            $hourStats = $this->getHourStats($date, $hour);
            $hourTotal = array_sum($hourStats);

            $activity[] = [
                'datetime' => $datetime->format('Y-m-d H:00'),
                'hour_stats' => $hourStats,
                'total' => $hourTotal,
            ];

            $total += $hourTotal;
        }

        return [
            'hourly_breakdown' => $activity,
            'total_messages' => $total,
            'average_per_hour' => round($total / $hours, 2),
        ];
    }

    /**
     * Clear cache counters with pattern matching
     */
    public function clearCacheCounters(?string $pattern = null): int
    {
        $cleared = 0;
        $today = now()->format('Y-m-d');

        // Default patterns
        $patterns = $pattern ? [$pattern] : [
            "whatsapp_hourly_{$today}_*",
            "whatsapp_bulk_*_{$today}_*",
            "whatsapp_informasi_{$today}_*",
        ];

        foreach ($patterns as $searchPattern) {
            $cleared += $this->clearCacheByPattern($searchPattern, $today);
        }

        return $cleared;
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'avg_processing_time' => $this->getAverageProcessingTime(),
            'success_rate' => $this->getSuccessRate(),
            'peak_hours' => $this->getPeakHours(),
            'throughput' => $this->getThroughputStats(),
        ];
    }

    /**
     * Get recommendations based on current state
     */
    public function getRecommendations(): array
    {
        $stats = $this->getQueueStats();
        $health = $this->getHealthStatus();
        $recommendations = [];

        // High queue volume
        if ($stats['pending_jobs'] > 10000) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Critical: Very high queue volume detected',
                'action' => 'Scale up queue workers immediately',
            ];
        } elseif ($stats['pending_jobs'] > 5000) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'High queue volume detected',
                'action' => 'Consider scaling up queue workers',
            ];
        }

        // High failure rate
        if ($stats['failed_jobs'] > 100) {
            $recommendations[] = [
                'type' => 'error',
                'message' => 'High failure rate detected',
                'action' => 'Review failed jobs and WhatsApp service connectivity',
            ];
        }

        // Rate limit concerns
        $currentHourStats = $this->getCurrentHourStats(now()->format('Y-m-d'), now()->format('H'));
        $totalCurrentHour = array_sum($currentHourStats);

        if ($totalCurrentHour > 2000) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'High message volume this hour',
                'action' => 'Monitor WhatsApp API rate limits closely',
            ];
        }

        return $recommendations;
    }

    // Protected helper methods
    protected function getProcessingJobsCount(): int
    {
        try {
            return DB::table('jobs')
                ->where('reserved_at', '>', 0)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function getJobDistribution(): array
    {
        try {
            return DB::table('jobs')
                ->select('payload')
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $className = $payload['displayName'] ?? 'Unknown';
                    return basename(str_replace('\\', '/', $className));
                })
                ->countBy()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getCurrentHourStats(string $date, string $hour): array
    {
        return [
            'presensi' => Cache::get("whatsapp_hourly_{$date}_{$hour}", 0),
            'alfa' => Cache::get("whatsapp_bulk_alfa_{$date}_{$hour}", 0),
            'mangkir' => Cache::get("whatsapp_bulk_mangkir_{$date}_{$hour}", 0),
            'bolos' => Cache::get("whatsapp_bulk_bolos_{$date}_{$hour}", 0),
            'informasi' => Cache::get("whatsapp_informasi_{$date}_{$hour}", 0),
        ];
    }

    protected function getHourStats(string $date, string $hour): array
    {
        return $this->getCurrentHourStats($date, str_pad($hour, 2, '0', STR_PAD_LEFT));
    }

    protected function getDailyStats(string $date): array
    {
        $dailyStats = ['presensi' => 0, 'alfa' => 0, 'mangkir' => 0, 'bolos' => 0, 'informasi' => 0];

        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $hourStats = $this->getCurrentHourStats($date, $hourStr);

            foreach ($hourStats as $type => $count) {
                $dailyStats[$type] += $count;
            }
        }

        return $dailyStats;
    }

    protected function getRateLimits(): array
    {
        return [
            'presensi' => ['messages_per_minute' => 35, 'max_delay_minutes' => 30],
            'bulk' => ['messages_per_minute' => 20, 'max_delay_hours' => 2],
            'informasi' => ['messages_per_minute' => 25, 'max_delay_minutes' => 60],
        ];
    }

    protected function getEstimatedProcessingTime(): array
    {
        $pendingJobs = Queue::size('default');
        $rateLimits = $this->getRateLimits();

        // Rough estimation assuming mixed job types
        $avgRate = 25; // messages per minute
        $estimatedMinutes = $pendingJobs > 0 ? ceil($pendingJobs / $avgRate) : 0;

        return [
            'pending_jobs' => $pendingJobs,
            'estimated_minutes' => $estimatedMinutes,
            'estimated_completion' => $estimatedMinutes > 0 ? now()->addMinutes($estimatedMinutes)->format('Y-m-d H:i:s') : null,
        ];
    }

    protected function assessQueueHealth(int $pendingJobs, int $failedJobs): array
    {
        $status = 'good';
        $message = 'Queue is operating normally';

        if ($pendingJobs > 10000 || $failedJobs > 100) {
            $status = 'critical';
            $message = 'Queue requires immediate attention';
        } elseif ($pendingJobs > 5000 || $failedJobs > 20) {
            $status = 'warning';
            $message = 'Queue should be monitored closely';
        }

        return [
            'status' => $status,
            'message' => $message,
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs
        ];
    }

    protected function assessRateLimitHealth(array $currentHourStats): array
    {
        $total = array_sum($currentHourStats);
        $status = 'good';
        $message = 'Rate limits are within acceptable range';

        if ($total > 3000) {
            $status = 'critical';
            $message = 'Rate limits are being heavily stressed';
        } elseif ($total > 2000) {
            $status = 'warning';
            $message = 'Rate limits should be monitored';
        }

        return [
            'status' => $status,
            'message' => $message,
            'current_hour_total' => $total,
            'details' => $currentHourStats
        ];
    }

    protected function getOverallStatus(): string
    {
        $queueStats = $this->getQueueStats();
        $currentHourStats = $this->getCurrentHourStats(now()->format('Y-m-d'), now()->format('H'));

        $queueHealth = $this->assessQueueHealth($queueStats['pending_jobs'], $queueStats['failed_jobs']);
        $rateLimitHealth = $this->assessRateLimitHealth($currentHourStats);

        if ($queueHealth['status'] === 'critical' || $rateLimitHealth['status'] === 'critical') {
            return 'critical';
        }

        if ($queueHealth['status'] === 'warning' || $rateLimitHealth['status'] === 'warning') {
            return 'warning';
        }

        return 'good';
    }

    protected function getActiveAlerts(): array
    {
        $alerts = [];
        $stats = $this->getQueueStats();

        if ($stats['pending_jobs'] > 10000) {
            $alerts[] = ['type' => 'critical', 'message' => "Critical queue backlog: {$stats['pending_jobs']} jobs"];
        }

        if ($stats['failed_jobs'] > 100) {
            $alerts[] = ['type' => 'error', 'message' => "High failure rate: {$stats['failed_jobs']} failed jobs"];
        }

        return $alerts;
    }

    protected function clearCacheByPattern(string $pattern, string $today): int
    {
        $cleared = 0;

        // Generate possible cache keys based on pattern
        if (str_contains($pattern, 'whatsapp_hourly_')) {
            for ($hour = 0; $hour < 24; $hour++) {
                $key = "whatsapp_hourly_{$today}_" . str_pad($hour, 2, '0', STR_PAD_LEFT);
                if (Cache::forget($key)) $cleared++;
            }
        }

        // Add more pattern matching logic as needed
        $bulkTypes = ['alfa', 'mangkir', 'bolos'];
        foreach ($bulkTypes as $type) {
            if (str_contains($pattern, "whatsapp_bulk_{$type}_") || str_contains($pattern, 'whatsapp_bulk_*_')) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $key = "whatsapp_bulk_{$type}_{$today}_" . str_pad($hour, 2, '0', STR_PAD_LEFT);
                    if (Cache::forget($key)) $cleared++;
                }
            }
        }

        return $cleared;
    }

    protected function getAverageProcessingTime(): ?float
    {
        // This would require additional logging/tracking
        // Return null for now as it requires more infrastructure
        return null;
    }

    protected function getSuccessRate(): ?float
    {
        try {
            $totalProcessed = 0;
            $failed = DB::table('failed_jobs')->count();

            // Try to get total processed jobs from jobs table (if available)
            try {
                $pending = DB::table('jobs')->count();
                $totalProcessed = $pending + $failed;
            } catch (\Exception $e) {
                // If jobs table doesn't exist or error, calculate differently
                // Use cache data if available
                $today = now()->format('Y-m-d');
                $successKey = "whatsapp_metrics_success_{$today}";
                $errorKey = "whatsapp_metrics_error_{$today}";

                $successData = Cache::get($successKey, ['count' => 0]);
                $errorData = Cache::get($errorKey, ['count' => 0]);

                $totalProcessed = $successData['count'] + $errorData['count'] + $failed;
            }

            return $totalProcessed > 0 ? round(($totalProcessed - $failed) / $totalProcessed * 100, 2) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getPeakHours(): array
    {
        // This would require historical data analysis
        // Return default peak hours for now
        return [
            'morning' => ['07', '08', '09'],
            'afternoon' => ['13', '14', '15'],
            'evening' => ['16', '17', '18']
        ];
    }

    protected function getThroughputStats(): array
    {
        $recentActivity = $this->getRecentActivity(3);
        return [
            'last_3_hours_total' => $recentActivity['total_messages'],
            'average_per_hour' => $recentActivity['average_per_hour'],
        ];
    }
}
