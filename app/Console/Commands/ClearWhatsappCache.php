<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearWhatsappCache extends Command
{
    protected $signature = 'whatsapp:clear-cache';

    protected $description = 'Clear WhatsApp rate limiting cache';

    public function handle()
    {
        $today = now()->format('Y-m-d');

        for ($hour = 0; $hour < 24; $hour++) {
            $hourStr = str_pad($hour, 2, '0', STR_PAD_LEFT);
            Cache::forget("whatsapp_hourly_{$today}_{$hourStr}");
            Cache::forget("whatsapp_informasi_hourly_{$today}_{$hourStr}");
        }

        Cache::forget('jadwal_presensi:'.now()->isoFormat('dddd'));

        $this->info('WhatsApp cache cleared successfully');

        return 0;
    }
}
