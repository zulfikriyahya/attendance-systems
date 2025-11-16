<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKetidakhadiran;
use Illuminate\Console\Command;

class SetKetidakhadiran extends Command
{
    protected $signature = 'presensi:set-ketidakhadiran';

    protected $description = 'Dispatch job pengecekan presensi.';

    public function handle(): void
    {
        ProcessKetidakhadiran::dispatch();
        $this->info('Job ProcessKetidakhadiran sudah dikirim ke queue.');
    }
}
