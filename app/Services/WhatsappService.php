<?php

namespace App\Services;

class WhatsappService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.whatsapp.endpoint');
    }

    public function send(string $nomor, string $pesan, ?string $filePath = null): array
    {
        $nomor = $this->normalizeNumber($nomor);

        $postFields = [
            'message' => $pesan,
            'number' => $nomor,
        ];

        if ($filePath && file_exists($filePath)) {
            $postFields['file_dikirim'] = new \CURLFile(realpath($filePath));
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            return [
                'status' => false,
                'error' => curl_error($curl),
            ];
        }

        curl_close($curl);

        return json_decode($response, true) ?? [
            'status' => false,
            'error' => 'Invalid JSON response',
        ];
    }

    /**
     * Kirim notifikasi presensi (unified method)
     */
    public function sendPresensi(
        string $nomor,
        string $jenis,
        string $status,
        string $waktu,
        string $nama,
        bool $isSiswa,
        string $instansi,
        bool $isBulk = false
    ): array {
        $ikon = $jenis === 'Presensi Masuk' ? 'Masuk' : 'Pulang';
        $tanggal = now()->translatedFormat('d F Y');
        $tahunIni = date('Y');

        // Different closing messages for bulk vs real-time
        if ($isBulk) {
            $penutup = $isSiswa
                ? ($jenis === 'Presensi Masuk'
                    ? 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.'
                    : 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.')
                : ($jenis === 'Presensi Masuk'
                    ? 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.'
                    : 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.');
        } else {
            $penutup = $isSiswa
                ? ($jenis === 'Presensi Masuk'
                    ? 'Selamat mengikuti kegiatan pembelajaran hari ini.'
                    : 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.')
                : ($jenis === 'Presensi Masuk'
                    ? 'Selamat menjalankan tugas dan tanggung jawab Anda.'
                    : 'Terima kasih atas dedikasi dan kinerja Anda hari ini.');
        }

        $pesan = <<<TEXT
        *PTSP MTSN 1 PANDEGLANG*
        ———————————————————
        *Presensi {$ikon}*
        ———————————————————
        Nama : {$nama}
        Status : *{$status}*
        Tanggal : {$tanggal}
        Waktu : {$waktu} WIB
        ———————————————————
        {$penutup}

        *© 2022 - {$tahunIni} {$instansi}*
        TEXT;

        return $this->send($nomor, $pesan);
    }

    /**
     * Kirim informasi (unified method)
     */
    public function sendInformasi(
        string $nomor,
        string $judul,
        string $isi,
        string $nama = 'User',
        string $instansi = 'Instansi',
        ?string $lampiran = null,
        bool $isSiswa = false
    ): array {
        $tanggalFormatted = now()->translatedFormat('d F Y');
        $tahunIni = date('Y');

        // Batasi isi pesan
        $isiSingkat = strlen($isi) > 200
            ? substr($isi, 0, 200) . '...'
            : $isi;

        // Greeting berdasarkan tipe user
        $greeting = $isSiswa
            ? "Kepada Siswa/i yang terhormat,"
            : "Kepada Bapak/Ibu yang terhormat,";

        $closing = $isSiswa
            ? "Terima kasih atas perhatiannya. Tetap semangat belajar!"
            : "Terima kasih atas perhatian dan kerjasamanya.";

        $pesan = <<<TEXT
        📢 *INFORMASI TERBARU*
        {$instansi}
        ———————————————————

        {$greeting}

        *{$judul}*

        {$isiSingkat}

        🗓️ Tanggal: {$tanggalFormatted}
        👤 Penerima: {$nama}

        ———————————————————
        {$closing}

        *© 2022 - {$tahunIni} {$instansi}*
        TEXT;

        // Kirim pesan utama
        $result = $this->send($nomor, $pesan);

        // Kirim lampiran jika ada dan pesan berhasil
        if ($result['status'] && $lampiran && file_exists(storage_path('app/public/' . $lampiran))) {
            sleep(2); // Delay untuk memastikan pesan pertama terkirim

            $filePath = storage_path('app/public/' . $lampiran);
            $namaFile = basename($lampiran);

            $this->send($nomor, "📎 Lampiran: {$namaFile}", $filePath);
        }

        return $result;
    }

    protected function normalizeNumber(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '62')) {
            return '08' . substr($nomor, 2);
        }
        if (str_starts_with($nomor, '8')) {
            return '08' . substr($nomor, 1);
        }
        if (str_starts_with($nomor, '08')) {
            return $nomor;
        }

        return $nomor;
    }

    /**
     * Validasi nomor telepon sebelum mengirim
     */
    public function validatePhoneNumbers(array $nomors): array
    {
        $valid = [];
        $invalid = [];

        foreach ($nomors as $nomor) {
            $normalized = $this->normalizeNumber($nomor);

            // Validasi format nomor Indonesia
            if (preg_match('/^08[0-9]{8,12}$/', $normalized)) {
                $valid[] = $normalized;
            } else {
                $invalid[] = $nomor;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }
}
