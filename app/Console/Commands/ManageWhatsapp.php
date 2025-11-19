<?php

namespace App\Console\Commands;

use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class ManageWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:manage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive WhatsApp management CLI - All-in-one management tool';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayHeader();

        while (true) {
            $choice = $this->showMainMenu();

            switch ($choice) {
                case '1':
                    $this->showHealthStatus();
                    break;
                case '2':
                    $this->showRateLimits();
                    break;
                case '3':
                    $this->showDailyUsage();
                    break;
                case '4':
                    $this->showQueueStatus();
                    break;
                case '5':
                    $this->resetCircuitBreaker();
                    break;
                case '6':
                    $this->resetRateLimitsMenu();
                    break;
                case '7':
                    $this->cleanupCache();
                    break;
                case '8':
                    $this->showLogs();
                    break;
                case '9':
                    $this->testConnection();
                    break;
                case '10':
                    $this->testRateLimits();
                    break;
                case '11':
                    $this->realTimeMonitor();
                    break;
                case '12':
                    $this->dispatchPresensiJob();
                    break;
                case '0':
                    $this->info("\n👋 Terima kasih! Sampai jumpa.\n");

                    return 0;
                default:
                    $this->error('❌ Pilihan tidak valid!');
            }

            if ($choice !== '0' && $choice !== '11') {
                $this->newLine();
                $this->ask('Tekan ENTER untuk melanjutkan...');
            }
        }
    }

    protected function displayHeader()
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════╗');
        $this->line('║   📱 WhatsApp Management CLI v2.0                 ║');
        $this->line('║   Kelola & Monitor WhatsApp Service               ║');
        $this->line('║   All Commands Integrated                         ║');
        $this->line('╚═══════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showMainMenu(): string
    {
        $this->line('┌──────────────────────────────────────────────────┐');
        $this->line('│  📋 MENU UTAMA                                   │');
        $this->line('├──────────────────────────────────────────────────┤');
        $this->line('│  MONITORING & STATUS                             │');
        $this->line('│  [1]  🏥 Status Kesehatan Service                │');
        $this->line('│  [2]  ⚡ Rate Limits & Usage                     │');
        $this->line('│  [3]  📊 Daily Usage Summary                     │');
        $this->line('│  [4]  📮 Queue Status                            │');
        $this->line('│  [11] 📺 Real-Time Monitor                       │');
        $this->line('├──────────────────────────────────────────────────┤');
        $this->line('│  MANAGEMENT & CONTROL                            │');
        $this->line('│  [5]  🔄 Reset Circuit Breaker                   │');
        $this->line('│  [6]  🔧 Reset Rate Limits (Advanced)            │');
        $this->line('│  [7]  🧹 Cleanup Cache                           │');
        $this->line('│  [12] 📤 Dispatch Presensi Job                   │');
        $this->line('├──────────────────────────────────────────────────┤');
        $this->line('│  TESTING & LOGS                                  │');
        $this->line('│  [8]  📝 Show Recent Logs                        │');
        $this->line('│  [9]  🔌 Test Connection                         │');
        $this->line('│  [10] 🧪 Test Rate Limits                        │');
        $this->line('├──────────────────────────────────────────────────┤');
        $this->line('│  [0]  🚪 Exit                                    │');
        $this->line('└──────────────────────────────────────────────────┘');
        $this->newLine();

        return $this->ask('Pilih menu');
    }

    protected function showHealthStatus()
    {
        $this->info('🏥 Memeriksa Status Kesehatan...');
        $this->newLine();

        try {
            $service = app(WhatsappService::class);
            $health = $service->getHealthStatus();

            // Status Badge
            $statusBadge = match ($health['status']) {
                'healthy' => '<fg=green>✓ HEALTHY</>',
                'degraded' => '<fg=yellow>⚠ DEGRADED</>',
                'unhealthy' => '<fg=red>✗ UNHEALTHY</>',
                default => '<fg=gray>? UNKNOWN</>'
            };

            $this->line("Status: {$statusBadge}");
            $this->newLine();

            // Performance
            $this->line('📈 <fg=cyan>Performance Metrics:</>');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Success Rate', $health['performance']['success_rate'].'%'],
                    ['Total Sent', number_format($health['performance']['total_sent'])],
                    ['Success Count', number_format($health['performance']['success_count'])],
                    ['Error Count', number_format($health['performance']['error_count'])],
                    ['Avg Response Time', round($health['performance']['avg_response_time'], 2).' ms'],
                ]
            );
            $this->newLine();

            // Circuit Breaker
            $circuitStatus = $health['circuit_breaker']['is_open']
                ? '<fg=red>🔴 OPEN (PAUSED)</>'
                : '<fg=green>✓ CLOSED (ACTIVE)</>';

            $this->line('🔒 <fg=cyan>Circuit Breaker:</>');
            $this->line("   Status: {$circuitStatus}");
            $this->line("   Error Count: {$health['circuit_breaker']['error_count']}");
            $this->line("   Threshold: {$health['circuit_breaker']['error_threshold']}");

            if ($health['circuit_breaker']['is_open']) {
                $this->newLine();
                $this->warn('⚠️  Circuit breaker terbuka! Service di-pause untuk mencegah overload.');
                $this->line('   Gunakan menu [5] untuk reset manual jika diperlukan.');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function showRateLimits()
    {
        $this->info('⚡ Memeriksa Rate Limits...');
        $this->newLine();

        try {
            $service = app(WhatsappService::class);
            $health = $service->getHealthStatus();

            // Global Hourly
            $this->line('🌍 <fg=cyan>Global Hourly Limit:</>');
            $hourlyPercentage = $health['rate_limits']['global_hourly']['percentage'];
            $hourlyColor = $hourlyPercentage > 80 ? 'red' : ($hourlyPercentage > 60 ? 'yellow' : 'green');

            $this->line("   Used: {$health['rate_limits']['global_hourly']['used']} / {$health['rate_limits']['global_hourly']['limit']}");
            $this->line("   Usage: <fg={$hourlyColor}>{$hourlyPercentage}%</>");
            $this->line("   Remaining: {$health['rate_limits']['global_hourly']['remaining']}");
            $this->newLine();

            // Global Daily
            $this->line('📅 <fg=cyan>Global Daily Limit:</>');
            $dailyPercentage = $health['rate_limits']['global_daily']['percentage'];
            $dailyColor = $dailyPercentage > 80 ? 'red' : ($dailyPercentage > 60 ? 'yellow' : 'green');

            $this->line("   Used: {$health['rate_limits']['global_daily']['used']} / {$health['rate_limits']['global_daily']['limit']}");
            $this->line("   Usage: <fg={$dailyColor}>{$dailyPercentage}%</>");
            $this->line("   Remaining: {$health['rate_limits']['global_daily']['remaining']}");
            $this->newLine();

            // Category Limits
            $this->line('📦 <fg=cyan>Category Daily Limits:</>');
            $categories = ['presensi', 'bulk', 'informasi'];
            $data = [];

            foreach ($categories as $cat) {
                if (isset($health['rate_limits'][$cat])) {
                    $limit = $health['rate_limits'][$cat];
                    $color = $limit['percentage'] > 80 ? 'red' : ($limit['percentage'] > 60 ? 'yellow' : 'green');
                    $status = $limit['percentage'] > 80 ? '🔴' : ($limit['percentage'] > 60 ? '🟡' : '🟢');

                    $data[] = [
                        ucfirst($cat),
                        "{$limit['used']} / {$limit['limit']}",
                        "<fg={$color}>{$limit['percentage']}%</>",
                        $limit['remaining'],
                        $status,
                    ];
                }
            }

            $this->table(['Category', 'Used/Limit', 'Usage', 'Remaining', 'Status'], $data);

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function showDailyUsage()
    {
        $this->info('📊 Daily Usage Summary...');
        $this->newLine();

        try {
            $now = now();
            $date = $now->format('Y-m-d');

            $summary = [
                'date' => $date,
                'day_of_week' => $now->translatedFormat('l'),
                'global_total' => Cache::get("whatsapp_global_daily_{$date}", 0),
                'by_type' => [
                    'presensi' => Cache::get("whatsapp_presensi_daily_{$date}", 0),
                    'bulk' => Cache::get("whatsapp_bulk_daily_{$date}", 0),
                    'informasi' => Cache::get("whatsapp_informasi_daily_{$date}", 0),
                ],
            ];

            // Get limits
            $limits = [
                'global' => config('whatsapp.rate_limits.global.daily', 5000),
                'presensi' => config('whatsapp.rate_limits.presensi.messages_per_day', 3000),
                'bulk' => config('whatsapp.rate_limits.bulk.messages_per_day', 1500),
                'informasi' => config('whatsapp.rate_limits.informasi.messages_per_day', 1000),
            ];

            $this->line("📅 Date: <fg=cyan>{$summary['date']}</> ({$summary['day_of_week']})");
            $this->line('🌍 Global Total: <fg=yellow>'.number_format($summary['global_total']).'</> / '.number_format($limits['global']));
            $globalPercentage = $limits['global'] > 0 ? round(($summary['global_total'] / $limits['global']) * 100, 2) : 0;
            $this->line("   Usage: {$globalPercentage}%");
            $this->newLine();

            $data = [];
            foreach (['presensi', 'bulk', 'informasi'] as $type) {
                $used = $summary['by_type'][$type];
                $limit = $limits[$type];
                $percentage = $limit > 0 ? round(($used / $limit) * 100, 2) : 0;
                $color = $percentage > 80 ? 'red' : ($percentage > 60 ? 'yellow' : 'green');

                $data[] = [
                    ucfirst($type),
                    number_format($used),
                    number_format($limit),
                    "<fg={$color}>{$percentage}%</>",
                    number_format($limit - $used),
                ];
            }

            $this->table(
                ['Category', 'Sent', 'Limit', 'Usage', 'Remaining'],
                $data
            );

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function showQueueStatus()
    {
        $this->info('📮 Memeriksa Queue Status...');
        $this->newLine();

        try {
            $queueSize = Queue::size('whatsapp');
            $warningThreshold = config('whatsapp.monitoring.thresholds.queue.warning', 5000);
            $criticalThreshold = config('whatsapp.monitoring.thresholds.queue.critical', 10000);

            $status = 'NORMAL';
            $color = 'green';
            $icon = '🟢';

            if ($queueSize >= $criticalThreshold) {
                $status = 'CRITICAL';
                $color = 'red';
                $icon = '🔴';
            } elseif ($queueSize >= $warningThreshold) {
                $status = 'HIGH';
                $color = 'yellow';
                $icon = '🟡';
            }

            $this->line("📊 Queue Size: <fg={$color}>".number_format($queueSize).'</>');
            $this->line("🚦 Status: <fg={$color}>{$icon} {$status}</>");
            $this->newLine();
            $this->line('⚠️  Warning Threshold: '.number_format($warningThreshold));
            $this->line('🔴 Critical Threshold: '.number_format($criticalThreshold));

            if ($queueSize >= $criticalThreshold) {
                $this->newLine();
                $this->error('⚠️  CRITICAL: Queue terlalu penuh!');
                $this->warn('   Rekomendasi: Tambah queue workers atau periksa masalah processing.');
            } elseif ($queueSize >= $warningThreshold) {
                $this->newLine();
                $this->warn('⚠️  WARNING: Queue mulai tinggi, monitor dengan cermat.');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function resetCircuitBreaker()
    {
        $this->warn('⚠️  Reset Circuit Breaker akan mengaktifkan kembali WhatsApp service.');
        $this->warn('   Pastikan masalah yang menyebabkan circuit breaker terbuka sudah teratasi!');
        $this->newLine();

        if (! $this->confirm('🔄 Yakin ingin reset Circuit Breaker?', false)) {
            $this->warn('❌ Dibatalkan.');

            return;
        }

        try {
            $circuitKey = 'whatsapp_circuit_breaker_open';
            $now = now();
            $errorKey = 'whatsapp_errors_'.$now->format('Y-m-d-H');

            $wasOpen = Cache::has($circuitKey);
            $errorCount = Cache::get($errorKey, 0);

            Cache::forget($circuitKey);
            Cache::forget($errorKey);

            $this->newLine();
            $this->info('✅ Circuit Breaker berhasil di-reset!');
            if ($wasOpen) {
                $this->line("   Previous error count: {$errorCount}");
                $this->line('   Service WhatsApp aktif kembali.');
            } else {
                $this->line('   Circuit breaker tidak dalam status open.');
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function resetRateLimitsMenu()
    {
        $this->warn('⚠️  ADVANCED: Reset Rate Limits');
        $this->newLine();

        $typeChoices = [
            'all' => 'All Types',
            'presensi' => 'Presensi Only',
            'bulk' => 'Bulk Only',
            'informasi' => 'Informasi Only',
        ];

        $scopeChoices = [
            'all' => 'All Scopes (minute, hour, day, circuit)',
            'circuit' => 'Circuit Breaker Only',
            'minute' => 'Minute Limits Only',
            'hour' => 'Hourly Limits Only',
            'day' => 'Daily Limits Only',
        ];

        $type = $this->choice(
            'Pilih tipe yang akan di-reset:',
            array_values($typeChoices),
            0
        );

        $scope = $this->choice(
            'Pilih scope yang akan di-reset:',
            array_values($scopeChoices),
            0
        );

        // Get keys from values
        $typeKey = array_search($type, $typeChoices);
        $scopeKey = array_search($scope, $scopeChoices);

        $this->newLine();
        $this->warn("Akan mereset: Type={$type}, Scope={$scope}");
        $this->warn('⚠️  Ini akan menghapus semua counter usage yang sesuai!');
        $this->newLine();

        if (! $this->confirm('Lanjutkan?', false)) {
            $this->warn('❌ Dibatalkan.');

            return;
        }

        try {
            Artisan::call('whatsapp:reset-limits', [
                '--type' => $typeKey,
                '--scope' => $scopeKey,
                '--force' => true,
            ]);

            $output = Artisan::output();
            $this->line($output);

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function cleanupCache()
    {
        $this->info('🧹 Cache Cleanup Tool');
        $this->newLine();
        $this->line('Akan menghapus cache WhatsApp yang lebih lama dari 7 hari.');
        $this->line('Data 7 hari terakhir akan tetap disimpan.');
        $this->newLine();

        if (! $this->confirm('Jalankan cleanup?', true)) {
            $this->warn('❌ Dibatalkan.');

            return;
        }

        try {
            $deleted = 0;
            $now = now();

            $this->line('Memproses...');
            $progressBar = $this->output->createProgressBar(23);
            $progressBar->start();

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

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);
            $this->info('✅ Cache cleanup selesai!');
            $this->line("   {$deleted} cache keys berhasil dihapus.");
            $this->line('   Cleanup date: '.$now->format('Y-m-d H:i:s'));

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function showLogs()
    {
        $logTypes = [
            'all' => 'Semua Logs',
            'error' => 'Error Logs Only',
            'warning' => 'Warning Logs Only',
            'info' => 'Info Logs Only',
            'whatsapp' => 'WhatsApp Related Only',
        ];

        $type = $this->choice('Pilih jenis log:', array_values($logTypes), 0);
        $lines = $this->ask('Jumlah baris terakhir?', 50);

        $this->info("📝 Menampilkan {$lines} baris terakhir...");
        $this->newLine();

        try {
            $logFile = storage_path('logs/laravel.log');

            if (! file_exists($logFile)) {
                $this->warn('⚠️  File log tidak ditemukan.');

                return;
            }

            $command = "tail -n {$lines} {$logFile}";

            if ($type === 'WhatsApp Related Only') {
                $command .= " | grep -i 'whatsapp'";
            } elseif ($type !== 'Semua Logs') {
                $filter = strtolower(str_replace(' Logs Only', '', $type));
                $command .= " | grep -i '{$filter}'";
            }

            $output = shell_exec($command);

            if (empty($output)) {
                $this->warn('⚠️  Tidak ada log yang ditemukan.');
            } else {
                $this->line($output);
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }

    protected function testConnection()
    {
        $this->info('🔌 Testing WhatsApp Connection...');
        $this->newLine();

        try {
            $service = app(WhatsappService::class);

            $this->line('⏳ Mengirim test request...');

            $health = $service->getHealthStatus();

            $this->newLine();
            if ($health['status'] === 'healthy') {
                $this->info('✅ Koneksi berhasil!');
                $this->line('   WhatsApp service berjalan normal.');
                $this->line("   Success rate: {$health['performance']['success_rate']}%");
            } elseif ($health['status'] === 'degraded') {
                $this->warn('⚠️  Koneksi ada masalah (degraded).');
                $this->line("   Status: {$health['status']}");
                $this->line("   Success rate: {$health['performance']['success_rate']}%");
            } else {
                $this->error('❌ Koneksi tidak sehat!');
                $this->line("   Status: {$health['status']}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Connection test failed: {$e->getMessage()}");
        }
    }

    protected function testRateLimits()
    {
        $this->warn('🧪 Test Rate Limits');
        $this->warn('⚠️  Ini akan mengirim test messages ke nomor yang ditentukan!');
        $this->newLine();

        $count = $this->ask('Jumlah test messages?', 10);
        $type = $this->choice(
            'Pilih tipe message:',
            ['presensi', 'bulk', 'informasi'],
            0
        );

        if (! $this->confirm('Lanjutkan test?', false)) {
            $this->warn('❌ Test dibatalkan.');

            return;
        }

        try {
            Artisan::call('whatsapp:test-limits', [
                '--count' => $count,
                '--type' => $type,
            ]);

            $output = Artisan::output();
            $this->line($output);

        } catch (\Exception $e) {
            $this->error("❌ Test failed: {$e->getMessage()}");
        }
    }

    protected function realTimeMonitor()
    {
        $this->info('📺 Launching Real-Time Monitor...');
        $this->newLine();

        $refresh = $this->ask('Refresh interval (detik)?', 5);

        $this->warn('Press Ctrl+C untuk keluar dari monitor');
        $this->newLine();

        sleep(2);

        try {
            Artisan::call('whatsapp:monitor', [
                '--refresh' => $refresh,
            ]);

        } catch (\Exception $e) {
            $this->error("❌ Monitor error: {$e->getMessage()}");
        }
    }

    protected function dispatchPresensiJob()
    {
        $this->info('📤 Dispatch Presensi Job');
        $this->newLine();
        $this->line('Job ini akan mengirim pengecekan ketidakhadiran ke queue.');
        $this->newLine();

        if (! $this->confirm('Dispatch job sekarang?', true)) {
            $this->warn('❌ Dibatalkan.');

            return;
        }

        try {
            Artisan::call('presensi:set-ketidakhadiran');

            $this->newLine();
            $this->info('✅ Job ProcessKetidakhadiran berhasil di-dispatch!');
            $this->line('   Job akan diproses oleh queue worker.');

            // Show current queue size
            $queueSize = Queue::size('whatsapp');
            $this->line("   Current queue size: {$queueSize}");

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
        }
    }
}
