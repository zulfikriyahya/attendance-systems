<?php

namespace App\Console\Commands;

use App\Services\WhatsappService;
use Illuminate\Console\Command;

class TestWhatsappLimits extends Command
{
    protected $signature = 'whatsapp:test-limits 
                            {--count=10 : Number of test messages}
                            {--type=presensi : Message type}';

    protected $description = 'Test WhatsApp rate limiting behavior';

    public function handle(WhatsappService $whatsapp)
    {
        $count = (int) $this->option('count');
        $type = $this->option('type');

        $this->info("🧪 Testing rate limits with {$count} messages of type '{$type}'");
        $this->newLine();

        $results = [
            'sent' => 0,
            'rate_limited' => 0,
            'errors' => 0,
        ];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $result = $whatsapp->send(
                '0895351856267', // Test number
                "Test message #{$i} - ".now()->format('H:i:s'),
                null,
                $type
            );

            if ($result['status'] ?? false) {
                $results['sent']++;
            } elseif (str_contains($result['error'] ?? '', 'Rate limit')) {
                $results['rate_limited']++;
            } else {
                $results['errors']++;
            }

            $bar->advance();
            usleep(100000); // 100ms between attempts
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->info('📊 TEST RESULTS');
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Sent Successfully', $results['sent'], round(($results['sent'] / $count) * 100, 2).'%'],
                ['Rate Limited', $results['rate_limited'], round(($results['rate_limited'] / $count) * 100, 2).'%'],
                ['Errors', $results['errors'], round(($results['errors'] / $count) * 100, 2).'%'],
            ]
        );

        // Show current health
        $this->newLine();
        $health = $whatsapp->getHealthStatus();
        $this->info('Current Service Status: '.strtoupper($health['status']));

        return 0;
    }
}
