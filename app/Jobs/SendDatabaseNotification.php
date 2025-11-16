<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Informasi;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendDatabaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Informasi $informasi
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Query users yang harus menerima notifikasi
        $users = User::query()
            ->where('status', true)
            ->where(function ($query) {
                $query->whereHas('pegawai', fn ($q) => $q->where('jabatan_id', $this->informasi->jabatan_id))
                    ->orWhereHas('siswa', fn ($q) => $q->where('jabatan_id', $this->informasi->jabatan_id));
            })
            ->get();

        if ($users->isEmpty()) {
            logger()->warning('No users found for database notification', [
                'informasi_id' => $this->informasi->id,
                'jabatan_id' => $this->informasi->jabatan_id,
            ]);

            return;
        }

        // Kirim notifikasi database ke semua user
        Notification::make()
            ->title('Informasi Baru: '.$this->informasi->judul)
            ->body('Silakan cek informasi terbaru yang telah dipublikasikan.')
            ->success()
            ->sendToDatabase($users);

        logger()->info('Database notifications sent', [
            'informasi_id' => $this->informasi->id,
            'total_users' => $users->count(),
        ]);
    }
}
