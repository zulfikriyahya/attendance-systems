<?php

namespace App\Console;

use App\Services\WhatsappService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SetKetidakhadiran::class,
        Commands\MonitorWhatsapp::class,
        Commands\ResetWhatsappLimits::class,
        Commands\TestWhatsappLimits::class,
        Commands\ManageWhatsapp::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ============================================================
        // PRESENSI - Auto Check Ketidakhadiran
        // ============================================================

        // Cek presensi masuk (Alfa) - 1 jam setelah jam datang
        $schedule->command('presensi:set-ketidakhadiran')
            ->dailyAt('08:00') // Sesuaikan dengan jam datang + 1 jam
            ->name('presensi:check-masuk')
            ->onOneServer()
            ->withoutOverlapping(10); // timeout 10 menit

        // Cek presensi pulang (Mangkir/Bolos) - 1 jam setelah jam pulang
        $schedule->command('presensi:set-ketidakhadiran')
            ->dailyAt('17:00') // Sesuaikan dengan jam pulang + 1 jam
            ->name('presensi:check-pulang')
            ->onOneServer()
            ->withoutOverlapping(10);

        // ============================================================
        // WHATSAPP - Health Monitoring (Every 5 Minutes)
        // ============================================================

        $schedule->call(function () {
            $this->monitorWhatsappHealth();
        })
            ->everyFiveMinutes()
            ->name('whatsapp:health-check')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // ============================================================
        // WHATSAPP - Auto Reset Circuit Breaker (Hourly)
        // ============================================================

        $schedule->call(function () {
            $this->autoResetCircuitBreaker();
        })
            ->hourly()
            ->name('whatsapp:auto-reset-circuit')
            ->onOneServer()
            ->withoutOverlapping();

        // ============================================================
        // WHATSAPP - Daily Summary Report (23:55)
        // ============================================================

        $schedule->call(function () {
            $this->generateDailySummary();
        })
            ->dailyAt('23:55')
            ->name('whatsapp:daily-summary')
            ->onOneServer();

        // ============================================================
        // WHATSAPP - Weekly Cache Cleanup (Sunday 02:00)
        // ============================================================

        $schedule->call(function () {
            $this->cleanupOldCache();
        })
            ->weekly()
            ->sundays()
            ->at('02:00')
            ->name('whatsapp:cleanup-cache')
            ->onOneServer();

        // ============================================================
        // OPTIONAL - Queue Monitor (Every Minute)
        // ============================================================

        $schedule->call(function () {
            $this->monitorQueueHealth();
        })
            ->everyMinute()
            ->name('queue:health-check')
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    // ============================================================
    // HELPER METHODS - WhatsApp Monitoring
    // ============================================================

    /**
     * Monitor WhatsApp service health
     */
    protected function monitorWhatsappHealth(): void
    {
        try {
            $service = app(WhatsappService::class);
            $health = $service->getHealthStatus();

            // Log if status is not healthy
            if ($health['status'] !== 'healthy') {
                Log::warning('⚠️ WhatsApp service health degraded', [
                    'status' => $health['status'],
                    'success_rate' => $health['performance']['success_rate'].'%',
                    'global_hourly_usage' => $health['rate_limits']['global_hourly']['percentage'].'%',
                    'error_count' => $health['performance']['error_count'],
                ]);
            }

            // Critical alert if circuit breaker is open
            if ($health['circuit_breaker']['is_open']) {
                Log::critical('🔴 WhatsApp circuit breaker is OPEN! Service temporarily paused.', [
                    'error_count' => $health['circuit_breaker']['error_count'],
                    'threshold' => $health['circuit_breaker']['error_threshold'],
                    'status' => 'PAUSED',
                ]);
            }

            // Warning if usage > 80%
            $globalHourlyUsage = $health['rate_limits']['global_hourly']['percentage'] ?? 0;
            if ($globalHourlyUsage > 80) {
                Log::warning('⚠️ WhatsApp rate limit usage HIGH!', [
                    'global_hourly_usage' => $globalHourlyUsage.'%',
                    'used' => $health['rate_limits']['global_hourly']['used'],
                    'limit' => $health['rate_limits']['global_hourly']['limit'],
                    'recommendation' => 'Consider slowing down message dispatch',
                ]);
            }

            // Info log for healthy status (every hour only)
            if (now()->minute === 0 && $health['status'] === 'healthy') {
                Log::info('✅ WhatsApp service is healthy', [
                    'success_rate' => $health['performance']['success_rate'].'%',
                    'global_daily_used' => $health['rate_limits']['global_daily']['used'],
                    'global_daily_limit' => $health['rate_limits']['global_daily']['limit'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ WhatsApp health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Auto reset circuit breaker if cooldown expired
     */
    protected function autoResetCircuitBreaker(): void
    {
        try {
            $circuitKey = 'whatsapp_circuit_breaker_open';

            // If circuit breaker is not open, safe to reset error counter
            if (! Cache::has($circuitKey)) {
                $now = now();
                $errorKey = 'whatsapp_errors_'.$now->format('Y-m-d-H');
                $errorCount = Cache::get($errorKey, 0);

                // Log before reset (only if there were errors)
                if ($errorCount > 0) {
                    Log::info('🔄 Auto-resetting WhatsApp error counter', [
                        'error_count' => $errorCount,
                        'hour' => $now->format('Y-m-d H:00'),
                    ]);

                    Cache::forget($errorKey);
                }
            } else {
                Log::info('⏸️ Circuit breaker is still OPEN, skipping error counter reset', [
                    'status' => 'WAITING_FOR_COOLDOWN',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Auto-reset circuit breaker failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate daily WhatsApp usage summary
     */
    protected function generateDailySummary(): void
    {
        try {
            $now = now();
            $date = $now->format('Y-m-d');

            // Collect daily statistics
            $summary = [
                'date' => $date,
                'day_of_week' => $now->translatedFormat('l'),
                'global_total' => Cache::get("whatsapp_global_daily_{$date}", 0),
                'by_type' => [
                    'presensi' => Cache::get("whatsapp_presensi_daily_{$date}", 0),
                    'bulk' => Cache::get("whatsapp_bulk_daily_{$date}", 0),
                    'informasi' => Cache::get("whatsapp_informasi_daily_{$date}", 0),
                ],
                'limits' => [
                    'global_daily' => config('whatsapp.rate_limits.global.daily', 5000),
                    'presensi_daily' => config('whatsapp.rate_limits.presensi.messages_per_day', 3000),
                    'bulk_daily' => config('whatsapp.rate_limits.bulk.messages_per_day', 1500),
                    'informasi_daily' => config('whatsapp.rate_limits.informasi.messages_per_day', 1000),
                ],
            ];

            // Calculate usage percentages
            $summary['usage_percentage'] = [
                'global' => $summary['limits']['global_daily'] > 0
                    ? round(($summary['global_total'] / $summary['limits']['global_daily']) * 100, 2)
                    : 0,
                'presensi' => $summary['limits']['presensi_daily'] > 0
                    ? round(($summary['by_type']['presensi'] / $summary['limits']['presensi_daily']) * 100, 2)
                    : 0,
                'bulk' => $summary['limits']['bulk_daily'] > 0
                    ? round(($summary['by_type']['bulk'] / $summary['limits']['bulk_daily']) * 100, 2)
                    : 0,
                'informasi' => $summary['limits']['informasi_daily'] > 0
                    ? round(($summary['by_type']['informasi'] / $summary['limits']['informasi_daily']) * 100, 2)
                    : 0,
            ];

            Log::info('📊 WhatsApp Daily Summary', $summary);

            // Warning if any type exceeded 90%
            foreach ($summary['usage_percentage'] as $type => $percentage) {
                if ($percentage > 90) {
                    Log::warning("⚠️ High daily usage for {$type}: {$percentage}%");
                }
            }

        } catch (\Exception $e) {
            Log::error('❌ Daily summary generation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cleanup old cache keys (older than 7 days)
     */
    protected function cleanupOldCache(): void
    {
        try {
            $deleted = 0;
            $now = now();

            // Cleanup keys from last 30 days (keep last 7 days)
            for ($i = 8; $i <= 30; $i++) {
                $date = $now->copy()->subDays($i)->format('Y-m-d');

                $keysToDelete = [
                    "whatsapp_global_daily_{$date}",
                    "whatsapp_presensi_daily_{$date}",
                    "whatsapp_bulk_daily_{$date}",
                    "whatsapp_informasi_daily_{$date}",
                ];

                foreach ($keysToDelete as $key) {
                    if (Cache::forget($key)) {
                        $deleted++;
                    }
                }
            }

            Log::info('🧹 WhatsApp cache cleanup completed', [
                'deleted_keys' => $deleted,
                'cleanup_date' => $now->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Cache cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Monitor queue health and performance
     */
    protected function monitorQueueHealth(): void
    {
        try {
            // Get queue size for whatsapp queue
            $queueSize = Queue::size('whatsapp');

            // Warning thresholds
            $warningThreshold = config('whatsapp.monitoring.thresholds.queue.warning', 5000);
            $criticalThreshold = config('whatsapp.monitoring.thresholds.queue.critical', 10000);

            // Log warnings
            if ($queueSize >= $criticalThreshold) {
                Log::critical('🔴 WhatsApp queue CRITICAL!', [
                    'queue_size' => $queueSize,
                    'threshold' => $criticalThreshold,
                    'action_required' => 'Increase queue workers or check for issues',
                ]);
            } elseif ($queueSize >= $warningThreshold) {
                Log::warning('⚠️ WhatsApp queue HIGH!', [
                    'queue_size' => $queueSize,
                    'threshold' => $warningThreshold,
                    'recommendation' => 'Monitor closely',
                ]);
            }

            // Info log every 10 minutes only
            if (now()->minute % 10 === 0 && $queueSize > 0) {
                Log::info('📮 WhatsApp queue status', [
                    'queue_size' => $queueSize,
                    'status' => $queueSize < $warningThreshold ? 'NORMAL' : 'ELEVATED',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Queue health check failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
