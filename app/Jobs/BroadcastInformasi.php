<?php

// Jobs/BroadcastInformasi.php

namespace App\Jobs;

use App\Models\Informasi;
use App\Models\Pegawai;
use App\Models\Siswa;
use App\Services\WhatsappDelayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastInformasi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public function __construct(
        public Informasi $informasi
    ) {}

    public function handle(WhatsappDelayService $delayService): void
    {
        // Ambil semua siswa dengan nomor telepon yang valid
        $siswa = Siswa::with('jabatan.instansi', 'user')
            ->where('jabatan_id', $this->informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        // Ambil semua pegawai dengan nomor telepon yang valid
        $pegawai = Pegawai::with('jabatan.instansi', 'user')
            ->where('jabatan_id', $this->informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        $totalRecipients = $siswa->count() + $pegawai->count();

        // Jika tidak ada penerima, return
        if ($totalRecipients === 0) {
            logger()->warning('No recipients found for informasi broadcast', [
                'informasi_id' => $this->informasi->id,
                'judul' => $this->informasi->judul,
            ]);

            return;
        }

        $notifCounter = 0;
        $now = now(); // Ambil waktu sekali di awal

        // Proses pengiriman ke siswa
        foreach ($siswa as $student) {
            $nama = $student->user?->name ?? $student->nama ?? 'Siswa';
            $instansi = $student->jabatan?->instansi?->nama ?? 'Instansi';

            // Hitung delay SEBELUM dispatch
            $delay = $delayService->calculateInformasiDelay($notifCounter);
            $delaySeconds = $delay->diffInSeconds($now);

            SendWhatsappMessage::dispatch(
                $student->telepon,
                'informasi',
                [
                    'judul' => $this->informasi->judul,
                    'isi' => $this->informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $this->informasi->lampiran,
                    'isSiswa' => true,
                ]
            )
                ->delay($delaySeconds)
                ->onQueue('whatsapp');

            $notifCounter++;

            // Log setiap 50 pesan untuk monitoring
            if ($notifCounter % 50 === 0) {
                logger()->info("Broadcast progress: {$notifCounter}/{$totalRecipients} messages queued");
            }
        }

        // Proses pengiriman ke pegawai
        foreach ($pegawai as $employee) {
            $nama = $employee->user?->name ?? $employee->nama ?? 'Pegawai';
            $instansi = $employee->jabatan?->instansi?->nama ?? 'Instansi';

            // Hitung delay SEBELUM dispatch
            $delay = $delayService->calculateInformasiDelay($notifCounter);
            $delaySeconds = $delay->diffInSeconds($now);

            SendWhatsappMessage::dispatch(
                $employee->telepon,
                'informasi',
                [
                    'judul' => $this->informasi->judul,
                    'isi' => $this->informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $this->informasi->lampiran,
                    'isSiswa' => false,
                ]
            )
                ->delay($delaySeconds)
                ->onQueue('whatsapp');

            $notifCounter++;

            // Log setiap 50 pesan untuk monitoring
            if ($notifCounter % 50 === 0) {
                logger()->info("Broadcast progress: {$notifCounter}/{$totalRecipients} messages queued");
            }
        }

        // Log broadcast dengan estimasi waktu selesai
        $lastDelay = $delayService->calculateInformasiDelay($notifCounter - 1);
        $maxDelayMinutes = $lastDelay->diffInMinutes($now);

        logger()->info('Informasi WhatsApp broadcast dispatched', [
            'informasi_id' => $this->informasi->id,
            'judul' => $this->informasi->judul,
            'total_recipients' => $totalRecipients,
            'siswa' => $siswa->count(),
            'pegawai' => $pegawai->count(),
            'max_delay_minutes' => $maxDelayMinutes,
            'estimated_completion' => $lastDelay->format('Y-m-d H:i:s'),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error('Failed to broadcast informasi', [
            'informasi_id' => $this->informasi->id,
            'judul' => $this->informasi->judul,
            'error' => $exception->getMessage(),
        ]);
    }
}
