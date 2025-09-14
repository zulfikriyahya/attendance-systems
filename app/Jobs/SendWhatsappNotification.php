<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\WhatsappService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsappNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $nomor;

    public string $jenis;

    public string $status;

    public string $waktu;

    public string $nama;

    public bool $isSiswa;

    public string $instansi;

    public function __construct($nomor, $jenis, $status, $waktu, $nama, $isSiswa, $instansi)
    {
        $this->nomor = $nomor;
        $this->jenis = $jenis;
        $this->status = $status;
        $this->waktu = $waktu;
        $this->nama = $nama;
        $this->isSiswa = $isSiswa;
        $this->instansi = $instansi;
    }

    public function handle(WhatsappService $whatsapp): void
    {
        $ikon = $this->jenis === 'Presensi Masuk' ? 'Masuk' : 'Pulang';
        $tanggal = now()->translatedFormat('d F Y');
        $penutup = $this->isSiswa
            ? ($this->jenis === 'Presensi Masuk'
                ? 'Selamat mengikuti kegiatan pembelajaran hari ini.'
                : 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.')
            : ($this->jenis === 'Presensi Masuk'
                ? 'Selamat menjalankan tugas dan tanggung jawab Anda.'
                : 'Terima kasih atas dedikasi dan kinerja Anda hari ini.');

        $pesan = <<<TEXT
        *PTSP MTSN 1 PANDEGLANG*
        
        ———————————————————
        *Presensi {$ikon}*
        ———————————————————
        Nama    : {$this->nama}
        Status  : *{$this->status}*
        Tanggal : {$tanggal}
        Waktu   : {$this->waktu} WIB
        ———————————————————

        {$penutup}
        
        *© 2022 - 2025 {$this->instansi}*
        TEXT;

        $whatsapp->send($this->nomor, $pesan);
    }
}
