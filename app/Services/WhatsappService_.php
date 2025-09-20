<?php

namespace App\Services;

class WhatsappService_
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

    protected function normalizeNumber(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '62')) {
            return '08'.substr($nomor, 2);
        }
        if (str_starts_with($nomor, '8')) {
            return '08'.substr($nomor, 1);
        }
        if (str_starts_with($nomor, '08')) {
            return $nomor;
        }

        return $nomor;
    }

    /**
     * Kirim broadcast informasi ke multiple nomor
     */
    public function broadcastInformasi(array $recipients, string $judul, string $isi, ?string $lampiran = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $result = $this->sendInformasi(
                $recipient['nomor'],
                $judul,
                $isi,
                $recipient['nama'],
                $recipient['instansi'] ?? 'Instansi',
                $lampiran
            );

            if ($result['status']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'nomor' => $recipient['nomor'],
                    'nama' => $recipient['nama'],
                    'error' => $result['error']
                ];
            }
        }

        return $results;
    }

    /**
     * Kirim informasi ke nomor tertentu
     */
    public function sendInformasi(
        string $nomor,
        string $judul,
        string $isi,
        string $nama = 'User',
        string $instansi = 'Instansi',
        ?string $lampiran = null
    ): array {
        $tanggal = now()->translatedFormat('d F Y');

        // Batasi isi pesan
        $isiSingkat = strlen($isi) > 200
            ? substr($isi, 0, 200) . '...'
            : $isi;

        $pesan = <<<TEXT
📢 *INFORMASI TERBARU*
{$instansi}
———————————————————

Kepada yang terhormat,

*{$judul}*

{$isiSingkat}

📅 Tanggal: {$tanggal}
👤 Penerima: {$nama}

———————————————————
Terima kasih atas perhatiannya.

*© 2022 - 2025 {$instansi}*
TEXT;

        // Kirim pesan utama
        $result = $this->send($nomor, $pesan);

        // Jika berhasil dan ada lampiran, kirim lampiran
        if ($result['status'] && $lampiran && file_exists(storage_path('app/public/' . $lampiran))) {
            $filePath = storage_path('app/public/' . $lampiran);
            $this->send($nomor, "📎 Lampiran: {$judul}", $filePath);
        }

        return $result;
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