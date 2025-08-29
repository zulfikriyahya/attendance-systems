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
}
