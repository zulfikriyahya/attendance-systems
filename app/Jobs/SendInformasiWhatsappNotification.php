<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\WhatsappService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendInformasiWhatsappNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $nomor;
    public string $judul;
    public string $isi;
    public string $tanggal;
    public string $nama;
    public bool $isSiswa;
    public string $instansi;
    public ?string $lampiran;

    public function __construct(
        string $nomor,
        string $judul,
        string $isi,
        string $tanggal,
        string $nama,
        bool $isSiswa,
        string $instansi,
        ?string $lampiran = null
    ) {
        $this->nomor = $nomor;
        $this->judul = $judul;
        $this->isi = $isi;
        $this->tanggal = $tanggal;
        $this->nama = $nama;
        $this->isSiswa = $isSiswa;
        $this->instansi = $instansi;
        $this->lampiran = $lampiran;
    }

    public function handle(WhatsappService $whatsapp): void
    {
        // Format tanggal
        $tanggalFormatted = now()->parse($this->tanggal)->translatedFormat('d F Y');

        $tahunIni = date('Y');

        // Batasi isi pesan agar tidak terlalu panjang
        $isiSingkat = strlen($this->isi) > 200
            ? substr($this->isi, 0, 200) . '...'
            : $this->isi;

        // Pesan khusus berdasarkan tipe user
        $greeting = $this->isSiswa
            ? "Kepada Siswa/i yang terhormat,"
            : "Kepada Bapak/Ibu yang terhormat,";

        $closing = $this->isSiswa
            ? "Terima kasih atas perhatiannya. Tetap semangat belajar!"
            : "Terima kasih atas perhatian dan kerjasamanya.";

        $pesan = <<<TEXT
        📢 *INFORMASI TERBARU*
        {$this->instansi}
        ———————————————————

        {$greeting}

        *{$this->judul}*

        {$isiSingkat}

        🗓️ Tanggal: {$tanggalFormatted}
        👤 Penerima: {$this->nama}

        ———————————————————
        {$closing}

        *© 2022 - {$tahunIni} {$this->instansi}*
        TEXT;

        // Kirim pesan utama
        $result = $whatsapp->send($this->nomor, $pesan);

        // Jika ada lampiran, kirim sebagai file terpisah dengan delay kecil
        if ($this->lampiran && file_exists(storage_path('app/public/' . $this->lampiran))) {
            // Delay untuk memastikan pesan pertama terkirim dulu
            sleep(2);

            $filePath = storage_path('app/public/' . $this->lampiran);
            $namaFile = basename($this->lampiran);

            $whatsapp->send(
                $this->nomor,
                "📎 Lampiran: {$namaFile}",
                $filePath
            );
        }

        // Log jika ada error (optional)
        if (!$result['status']) {
            logger()->error('WhatsApp notification failed', [
                'nomor' => $this->nomor,
                'judul' => $this->judul,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        }
    }
}
