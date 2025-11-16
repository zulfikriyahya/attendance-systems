<?php

namespace App\Jobs;

use App\Models\Siswa;
use App\Models\Pegawai;
use App\Models\Informasi;
use Illuminate\Bus\Queueable;
use App\Services\WhatsappDelayService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
        }

        // Log broadcast dengan estimasi waktu selesai
        $lastDelay = $delayService->calculateInformasiDelay($notifCounter - 1);
        $maxDelayMinutes = $lastDelay->diffInMinutes($now);
    }
}
