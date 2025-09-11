<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsappService;

class WhatsappStatusCommand extends Command
{
    protected $signature = 'whatsapp:status {--test : Test all endpoints}';
    protected $description = 'Check WhatsApp endpoints status';

    public function handle(WhatsappService $whatsappService)
    {
        if ($this->option('test')) {
            $this->testAllEndpoints($whatsappService);
        } else {
            $this->showEndpointStatus($whatsappService);
        }
    }

    protected function showEndpointStatus(WhatsappService $whatsappService)
    {
        $this->info('WhatsApp Endpoints Status:');
        $this->line('');

        $statuses = $whatsappService->getEndpointStatus();

        $headers = ['Endpoint', 'URL', 'Status', 'Last Checked'];
        $rows = [];

        foreach ($statuses as $key => $status) {
            $rows[] = [
                $key,
                $status['url'],
                $status['status'] === 'active' ? '<fg=green>ACTIVE</>' : '<fg=red>DOWN</>',
                $status['last_checked']
            ];
        }

        $this->table($headers, $rows);

        $activeCount = collect($statuses)->where('status', 'active')->count();
        $totalCount = count($statuses);

        $this->line('');
        $this->info("Active Endpoints: {$activeCount}/{$totalCount}");
    }

    protected function testAllEndpoints(WhatsappService $whatsappService)
    {
        $this->info('Testing all WhatsApp endpoints...');
        $this->line('');

        $results = $whatsappService->testAllEndpoints();

        $headers = ['Endpoint', 'URL', 'Status', 'Response Time', 'Error'];
        $rows = [];

        foreach ($results as $key => $result) {
            $status = $result['status'] === 'success' ? '<fg=green>SUCCESS</>' : '<fg=red>FAILED</>';
            $responseTime = $result['response_time_ms'] . 'ms';
            $error = $result['error'] ? substr($result['error'], 0, 50) . '...' : '-';

            $rows[] = [
                $key,
                $result['url'],
                $status,
                $responseTime,
                $error
            ];
        }

        $this->table($headers, $rows);

        $successCount = collect($results)->where('status', 'success')->count();
        $totalCount = count($results);

        $this->line('');
        $this->info("Successful Tests: {$successCount}/{$totalCount}");

        if ($successCount < $totalCount) {
            $this->warn('Some endpoints are not responding properly!');
        }
    }
}
