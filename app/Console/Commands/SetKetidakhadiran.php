<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessKetidakhadiran;

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
