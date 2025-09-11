<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\WhatsappService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsappNotificationBulk implements ShouldQueue
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
                ? 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.'
                : 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.')
            : ($this->jenis === 'Presensi Masuk'
                ? 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.'
                : 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.');

        $pesan = <<<TEXT
        *Presensi Online (POL)*
        _Dikembangkan dan dikelola oleh MTs Negeri 1 Pandeglang_

        —————————————
        *{$ikon}*
        Nama    : {$this->nama}
        Status  : *{$this->status}*
        Tanggal : {$tanggal}
        Waktu   : {$this->waktu} WIB
        —————————————

        {$penutup}
        _Jika ada kendala atau kesalahan teknis, silakan laporkan kepada kami. :)_
        
        *{$this->instansi}*
        _Sistem ini dikelola secara resmi oleh tim internal madrasah._
        TEXT;

        $whatsapp->send($this->nomor, $pesan);
    }
}
