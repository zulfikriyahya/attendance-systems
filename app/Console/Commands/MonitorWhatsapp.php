<?php

namespace App\Console\Commands;

use App\Services\WhatsappService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class MonitorWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:monitor 
                            {--refresh=5 : Refresh interval in seconds}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor WhatsApp service health and rate limits in real-time';

    protected WhatsappService $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        parent::__construct();
        $this->whatsapp = $whatsapp;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $refresh = (int) $this->option('refresh');
        $jsonOutput = $this->option('json');

        if ($jsonOutput) {
            $this->outputJson();

            return 0;
        }

        $this->info('🚀 WhatsApp Service Monitor');
        $this->info('Press Ctrl+C to exit');
        $this->newLine();

        while (true) {
            $this->displayDashboard();

            if ($refresh > 0) {
                sleep($refresh);
                // Clear screen for refresh
                if (PHP_OS_FAMILY !== 'Windows') {
                    system('clear');
                } else {
                    system('cls');
                }
            } else {
                break;
            }
        }

        return 0;
    }

    protected function displayDashboard(): void
    {
        $now = now();
        $health = $this->whatsapp->getHealthStatus();

        // Header
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('           📱 WHATSAPP SERVICE DASHBOARD                      ');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('Time: '.$now->format('Y-m-d H:i:s'));
        $this->newLine();

        // Overall Status
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'unhealthy' => 'red',
            default => 'white',
        };

        $statusIcon = match ($health['status']) {
            'healthy' => '✅',
            'degraded' => '⚠️',
            'unhealthy' => '❌',
            default => '❓',
        };

        $this->line("<fg={$statusColor}>Status: {$statusIcon} ".strtoupper($health['status']).'</>');
        $this->newLine();

        // Performance Metrics
        $this->info('📊 PERFORMANCE METRICS');
        $this->line('─────────────────────────────────────────────────────────────');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Success Rate', $health['performance']['success_rate'].'%'],
                ['Avg Response Time', round($health['performance']['avg_response_time'], 2).' ms'],
                ['Total Requests (Hour)', $health['performance']['total_requests']],
                ['Error Count (Hour)', $health['performance']['error_count']],
            ]
        );
        $this->newLine();

        // Rate Limits
        $this->info('🚦 RATE LIMITS STATUS');
        $this->line('─────────────────────────────────────────────────────────────');

        $rateLimitData = [
            [
                'Type',
                'Used',
                'Limit',
                'Percentage',
                'Status',
            ],
        ];

        foreach ($health['rate_limits'] as $type => $limit) {
            if (! isset($limit['percentage'])) {
                $percentage = $limit['limit'] > 0
                    ? round(($limit['used'] / $limit['limit']) * 100, 1)
                    : 0;
            } else {
                $percentage = $limit['percentage'];
            }

            $status = $percentage >= 90 ? '🔴 CRITICAL' :
                     ($percentage >= 70 ? '🟡 WARNING' : '🟢 OK');

            $rateLimitData[] = [
                ucwords(str_replace('_', ' ', $type)),
                $limit['used'],
                $limit['limit'],
                $percentage.'%',
                $status,
            ];
        }

        $this->table(
            ['Type', 'Used', 'Limit', 'Usage', 'Status'],
            array_slice($rateLimitData, 1)
        );
        $this->newLine();

        // Circuit Breaker
        $this->info('⚡ CIRCUIT BREAKER');
        $this->line('─────────────────────────────────────────────────────────────');

        $cbStatus = $health['circuit_breaker']['is_open'] ? '🔴 OPEN (Service Paused)' : '🟢 CLOSED (Normal)';
        $cbColor = $health['circuit_breaker']['is_open'] ? 'red' : 'green';

        $this->line("<fg={$cbColor}>Status: {$cbStatus}</>");
        $this->line('Errors (Hour): '.$health['circuit_breaker']['error_count'].' / '.$health['circuit_breaker']['error_threshold']);

        if ($health['circuit_breaker']['is_open']) {
            $cooldownUntil = Cache::get('whatsapp_circuit_breaker_open');
            if ($cooldownUntil) {
                $this->warn('⏰ Cooldown until: '.now()->addMinutes(30)->format('H:i:s'));
            }
        }

        $this->newLine();

        // Detailed Stats (Hourly breakdown per type)
        $this->info('📈 DETAILED STATISTICS (Current Hour)');
        $this->line('─────────────────────────────────────────────────────────────');

        $typeStats = [];
        foreach (['presensi', 'bulk', 'informasi'] as $type) {
            $hourlyKey = "whatsapp_{$type}_hourly_".$now->format('Y-m-d-H');
            $dailyKey = "whatsapp_{$type}_daily_".$now->format('Y-m-d');

            $typeStats[] = [
                ucfirst($type),
                Cache::get($hourlyKey, 0),
                Cache::get($dailyKey, 0),
            ];
        }

        $this->table(
            ['Type', 'This Hour', 'Today'],
            $typeStats
        );

        $this->line('═══════════════════════════════════════════════════════════════');
    }

    protected function outputJson(): void
    {
        $health = $this->whatsapp->getHealthStatus();
        $this->line(json_encode($health, JSON_PRETTY_PRINT));
    }
}
