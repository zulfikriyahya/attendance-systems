<?php

// Jobs/SendPengajuanKartuNotification.php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\PengajuanKartu;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPengajuanKartuNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries;

    public $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PengajuanKartu $pengajuanKartu,
        public string $notificationType // 'proses' atau 'selesai'
    ) {
        $this->tries = config('whatsapp.queue.retry_attempts', 3);
        $this->backoff = [120, 300, 600]; // 2 min, 5 min, 10 min
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = $this->pengajuanKartu;

        // Cek apakah ada nomor telepon
        $phoneNumber = null;
        $userName = $record->user->name;
        $isSiswa = false;

        // Cek apakah user adalah siswa atau pegawai
        if ($record->user->siswa) {
            $phoneNumber = $record->user->siswa->telepon;
            $isSiswa = true;
        } elseif ($record->user->pegawai) {
            $phoneNumber = $record->user->pegawai->telepon;
            $isSiswa = false;
        }

        // Jika tidak ada nomor telepon, skip
        if (! $phoneNumber) {
            logger()->warning('No phone number found for pengajuan kartu', [
                'pengajuan_id' => $record->id,
                'user_id' => $record->user->id,
                'user_name' => $userName,
                'type' => $this->notificationType,
            ]);

            return;
        }

        // Ambil data instansi
        $namaInstansi = \App\Models\Instansi::first()->nama ?? 'Instansi';
        $instansi = strtoupper($namaInstansi);

        // Dispatch job WhatsApp sesuai tipe notifikasi
        SendWhatsappMessage::dispatch(
            $phoneNumber,
            'pengajuan_kartu',
            [
                'nama' => $userName,
                'nomor_pengajuan' => $record->nomorPengajuanKartu,
                'instansi' => $instansi,
                'pengajuan_id' => $record->id,
                'notification_type' => $this->notificationType,
                'biaya' => $record->biaya,
                'isSiswa' => $isSiswa,
            ]
        )
            ->delay(now()->addSeconds(rand(5, 15))) // Small delay untuk natural
            ->onQueue('whatsapp');

        logger()->info('Pengajuan kartu notification dispatched', [
            'pengajuan_id' => $record->id,
            'nomor_pengajuan' => $record->nomorPengajuanKartu,
            'user_name' => $userName,
            'phone_number' => $phoneNumber,
            'notification_type' => $this->notificationType,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error('Failed to send pengajuan kartu notification', [
            'pengajuan_id' => $this->pengajuanKartu->id,
            'nomor_pengajuan' => $this->pengajuanKartu->nomorPengajuanKartu,
            'user_id' => $this->pengajuanKartu->user->id,
            'notification_type' => $this->notificationType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
