<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class MonitorWhatsappQueue extends Command
{
    protected $signature = 'whatsapp:monitor';
    protected $description = 'Monitor WhatsApp queue status and statistics';

    public function handle()
    {
        $this->info('WhatsApp Queue Monitoring');
        $this->info('========================');

        $pendingJobs = Queue::size('default');
        $this->line("Pending Jobs: {$pendingJobs}");

        $today = now()->format('Y-m-d');
        $currentHour = now()->format('H');

        $presensiKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $informasiKey = "whatsapp_informasi_hourly_{$today}_{$currentHour}";

        $presensiCount = Cache::get($presensiKey, 0);
        $informasiCount = Cache::get($informasiKey, 0);

        $this->line("Presensi notifications this hour: {$presensiCount}");
        $this->line("Informasi notifications this hour: {$informasiCount}");
        $this->line("Total notifications this hour: " . ($presensiCount + $informasiCount));

        $presensiLimit = config('whatsapp.rate_limits.presensi.messages_per_minute', 35);
        $informasiLimit = config('whatsapp.rate_limits.informasi.messages_per_minute', 25);

        $this->line("Rate limits - Presensi: {$presensiLimit}/min, Informasi: {$informasiLimit}/min");

        if ($pendingJobs > 5000) {
            $this->warn("High queue volume detected: {$pendingJobs} jobs pending");
        }

        return 0;
    }
}
