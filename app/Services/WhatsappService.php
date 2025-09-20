<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;

class WhatsappService
{
    protected string $endpoint;
    protected int $timeout;
    protected bool $loggingEnabled;
    protected bool $testingMode;
    protected array $config;

    public function __construct()
    {
        $this->config = config('whatsapp', []);
        $this->endpoint = $this->config['endpoint'] ?? config('services.whatsapp.endpoint');
        $this->timeout = $this->config['timeout'] ?? 15;
        $this->loggingEnabled = $this->config['logging']['enabled'] ?? true;
        $this->testingMode = $this->config['testing']['enabled'] ?? false;
    }

    /**
     * Send WhatsApp message with enhanced error handling and logging
     */
    public function send(string $nomor, string $pesan, ?string $filePath = null): array
    {
        $startTime = microtime(true);
        $nomor = $this->normalizeNumber($nomor);

        // Validation
        if (!$this->isValidNumber($nomor)) {
            return $this->buildErrorResponse('Invalid phone number format', $nomor);
        }

        // Testing mode
        if ($this->testingMode) {
            return $this->mockResponse($nomor, $pesan);
        }

        // Rate limiting check (optional circuit breaker)
        if ($this->isRateLimited()) {
            return $this->buildErrorResponse('Rate limit exceeded', $nomor);
        }

        try {
            $postFields = [
                'message' => $pesan,
                'number' => $nomor,
            ];

            if ($filePath && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $maxSize = $this->config['attachments']['max_size'] ?? 16777216; // 16MB

                if ($fileSize > $maxSize) {
                    return $this->buildErrorResponse('File too large', $nomor, ['file_size' => $fileSize, 'max_size' => $maxSize]);
                }

                $postFields['file_dikirim'] = new \CURLFile(realpath($filePath));
            }

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_USERAGENT => 'WhatsApp-Service/1.0',
                CURLOPT_SSL_VERIFYPEER => false, // Adjust based on your environment
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            $duration = round((microtime(true) - $startTime) * 1000, 2); // milliseconds

            if ($error) {
                $this->logError('CURL Error', $nomor, $error, ['duration_ms' => $duration]);
                return $this->buildErrorResponse($error, $nomor);
            }

            if ($httpCode >= 400) {
                $this->logError('HTTP Error', $nomor, "HTTP {$httpCode}", ['duration_ms' => $duration, 'response' => $response]);
                return $this->buildErrorResponse("HTTP {$httpCode}", $nomor);
            }

            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('JSON Decode Error', $nomor, json_last_error_msg(), ['duration_ms' => $duration, 'raw_response' => $response]);
                return $this->buildErrorResponse('Invalid JSON response', $nomor);
            }

            // Log successful send
            $this->logSuccess($nomor, $duration, $decodedResponse);

            // Update success metrics
            $this->updateMetrics('success', $duration);

            return $decodedResponse ?? ['status' => true, 'message' => 'Sent successfully'];
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError('Exception', $nomor, $e->getMessage(), ['duration_ms' => $duration, 'trace' => $e->getTraceAsString()]);
            $this->updateMetrics('error', $duration);

            return $this->buildErrorResponse($e->getMessage(), $nomor);
        }
    }

    /**
     * Enhanced sendPresensi with configuration support
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
        $templates = $this->config['templates']['presensi'] ?? [];
        $ikon = $jenis === 'Presensi Masuk' ? 'Masuk' : 'Pulang';
        $tanggal = now()->translatedFormat('l, d F Y');
        $tahunIni = date('Y');
        $urlPresensi = config('app.url');

        // Get template messages
        $messageType = $jenis === 'Presensi Masuk' ? 'masuk' : 'pulang';
        $userType = $isSiswa ? 'siswa' : 'pegawai';

        if ($isBulk) {
            $penutup = $templates[$messageType]['bulk_message'][$userType]
                ?? ($jenis === 'Presensi Masuk'
                    ? 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.'
                    : 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.');
        } else {
            $penutup = $templates[$messageType]['greeting'][$userType]
                ?? ($isSiswa
                    ? ($jenis === 'Presensi Masuk'
                        ? 'Selamat mengikuti kegiatan pembelajaran hari ini.'
                        : 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.')
                    : ($jenis === 'Presensi Masuk'
                        ? 'Selamat menjalankan tugas dan tanggung jawab Anda.'
                        : 'Terima kasih atas dedikasi dan kinerja Anda hari ini.'));
        }

        $header = str_replace('{instansi}', $instansi, $templates['header'] ?? 'PTSP {instansi}');
        $footer = str_replace(['{tahun}', '{instansi}'], [$tahunIni, $instansi], $templates['footer'] ?? '© 2022 - {tahun} {instansi}');

        $pesan = <<<TEXT
        *{$header}*

        ———————————————————
        *Presensi {$ikon}*
        ———————————————————
        Nama    : {$nama}
        Status  : *{$status}*
        Tanggal : {$tanggal}
        Waktu   : {$waktu} WIB
        ———————————————————
        Tautan  : {$urlPresensi}
        ———————————————————

        {$penutup}

        *{$footer}*
        TEXT;

        $result = $this->send($nomor, $pesan);

        // Track presensi-specific metrics
        $this->trackPresensiMetrics($jenis, $status, $isBulk, $result['status'] ?? false);

        return $result;
    }

    /**
     * Enhanced sendInformasi with better attachment handling
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
        $templates = $this->config['templates']['informasi'] ?? [];
        $tanggalFormatted = now()->translatedFormat('l, d F Y');
        $tahunIni = date('Y');

        // Configurable content length
        $maxLength = $templates['max_content_length'] ?? 200;
        $isiSingkat = strlen($isi) > $maxLength
            ? substr($isi, 0, $maxLength) . '...'
            : $isi;

        // Get template greetings and closings
        $userType = $isSiswa ? 'siswa' : 'pegawai';
        $greeting = $templates['greetings'][$userType]
            ?? ($isSiswa ? "Kepada Siswa/i yang terhormat," : "Kepada Bapak/Ibu yang terhormat,");

        $closing = $templates['closing'][$userType]
            ?? ($isSiswa ? "Terima kasih atas perhatiannya. Tetap semangat belajar!" : "Terima kasih atas perhatian dan kerjasamanya.");

        $header = str_replace('{instansi}', $instansi, $templates['header'] ?? 'PTSP {instansi}');
        $footer = str_replace(['{tahun}', '{instansi}'], [$tahunIni, $instansi], $templates['footer'] ?? '© 2022 - {tahun} {instansi}');

        $pesan = <<<TEXT
        *{$header}*

        ———————————————————
        📢 *INFORMASI TERBARU*
        ———————————————————
        {$greeting}

        *{$judul}*

        {$isiSingkat}

        🗓️ Tanggal: {$tanggalFormatted}
        👤 Penerima: {$nama}

        ———————————————————
        {$closing}

        *{$footer}*
        TEXT;

        // Send main message
        $result = $this->send($nomor, $pesan);

        // Handle attachment with better validation
        if ($result['status'] && $lampiran) {
            $result = $this->sendAttachment($nomor, $lampiran, $result);
        }

        // Track informasi-specific metrics
        $this->trackInformasiMetrics($judul, $lampiran !== null, $result['status'] ?? false);

        return $result;
    }

    /**
     * Send attachment with proper validation and error handling
     */
    protected function sendAttachment(string $nomor, string $lampiran, array $mainResult): array
    {
        $filePath = storage_path('app/public/' . $lampiran);

        if (!file_exists($filePath)) {
            $this->logError('Attachment Not Found', $nomor, "File not found: {$lampiran}");
            return array_merge($mainResult, ['attachment_error' => 'File not found']);
        }

        // Validate file type
        $extension = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
        $allowedTypes = $this->config['attachments']['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($extension, $allowedTypes)) {
            $this->logError('Invalid File Type', $nomor, "File type not allowed: {$extension}");
            return array_merge($mainResult, ['attachment_error' => 'File type not allowed']);
        }

        // Add delay to prevent rate limiting
        sleep(2);

        $namaFile = basename($lampiran);
        $attachmentResult = $this->send($nomor, "📎 Lampiran: {$namaFile}", $filePath);

        return array_merge($mainResult, ['attachment_result' => $attachmentResult]);
    }

    /**
     * Enhanced phone number validation
     */
    protected function isValidNumber(string $nomor): bool
    {
        if (!$this->config['phone']['validation']['enabled'] ?? true) {
            return true;
        }

        $pattern = $this->config['phone']['validation']['pattern'] ?? '/^08[0-9]{8,12}$/';
        return preg_match($pattern, $nomor) === 1;
    }

    /**
     * Enhanced number normalization with configuration
     */
    protected function normalizeNumber(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        $countryCode = $this->config['phone']['validation']['country_code'] ?? '62';
        $prefix = $this->config['phone']['normalization']['prefix'] ?? '08';

        if (str_starts_with($nomor, $countryCode)) {
            return $prefix . substr($nomor, strlen($countryCode));
        }
        if (str_starts_with($nomor, '8')) {
            return $prefix . substr($nomor, 1);
        }
        if (str_starts_with($nomor, $prefix)) {
            return $nomor;
        }

        return $nomor;
    }

    /**
     * Batch phone number validation with detailed results
     */
    public function validatePhoneNumbers(array $nomors): array
    {
        $valid = [];
        $invalid = [];
        $normalized = [];

        foreach ($nomors as $original) {
            $normalized_number = $this->normalizeNumber($original);

            if ($this->isValidNumber($normalized_number)) {
                $valid[] = $normalized_number;
                $normalized[$original] = $normalized_number;
            } else {
                $invalid[] = [
                    'original' => $original,
                    'normalized' => $normalized_number,
                    'reason' => 'Invalid format'
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'normalized_map' => $normalized,
            'summary' => [
                'total' => count($nomors),
                'valid_count' => count($valid),
                'invalid_count' => count($invalid),
                'success_rate' => count($nomors) > 0 ? round((count($valid) / count($nomors)) * 100, 2) : 0
            ]
        ];
    }

    /**
     * Check if service is rate limited
     */
    protected function isRateLimited(): bool
    {
        $key = 'whatsapp_rate_limit_' . date('Y-m-d-H');
        $limit = $this->config['rate_limits']['presensi']['messages_per_minute'] ?? 35;
        $current = Cache::get($key, 0);

        return $current >= ($limit * 60); // Convert per minute to per hour
    }

    /**
     * Mock response for testing
     */
    protected function mockResponse(string $nomor, string $pesan): array
    {
        $this->logInfo("MOCK: Message to {$nomor}", ['message_length' => strlen($pesan)]);

        return [
            'status' => true,
            'message' => 'Mock message sent successfully',
            'mock' => true,
            'recipient' => $nomor
        ];
    }

    /**
     * Build standardized error response
     */
    protected function buildErrorResponse(string $error, string $nomor, array $context = []): array
    {
        return [
            'status' => false,
            'error' => $error,
            'recipient' => $nomor,
            'timestamp' => now()->toISOString(),
            'context' => $context
        ];
    }

    /**
     * Enhanced logging methods
     */
    protected function logSuccess(string $nomor, float $duration, array $response): void
    {
        if (!$this->loggingEnabled) return;

        Log::channel($this->config['logging']['channels']['success'] ?? 'single')->info('WhatsApp message sent successfully', [
            'recipient' => $nomor,
            'duration_ms' => $duration,
            'response' => $this->config['logging']['context']['include_response'] ? $response : 'hidden'
        ]);
    }

    protected function logError(string $type, string $nomor, string $message, array $context = []): void
    {
        if (!$this->loggingEnabled) return;

        Log::channel($this->config['logging']['channels']['error'] ?? 'single')->error("WhatsApp {$type}", array_merge([
            'recipient' => $nomor,
            'error' => $message,
        ], $context));
    }

    protected function logInfo(string $message, array $context = []): void
    {
        if (!$this->loggingEnabled) return;

        Log::info($message, $context);
    }

    /**
     * Update performance metrics
     */
    protected function updateMetrics(string $type, float $duration): void
    {
        $key = "whatsapp_metrics_{$type}_" . date('Y-m-d-H');
        $data = Cache::get($key, ['count' => 0, 'total_duration' => 0]);

        $data['count']++;
        $data['total_duration'] += $duration;
        $data['avg_duration'] = $data['total_duration'] / $data['count'];

        Cache::put($key, $data, now()->addHours(25)); // Keep for analysis
    }

    /**
     * Track presensi-specific metrics
     */
    protected function trackPresensiMetrics(string $jenis, string $status, bool $isBulk, bool $success): void
    {
        $key = 'whatsapp_presensi_stats_' . date('Y-m-d');
        $stats = Cache::get($key, []);

        $stats['total'] = ($stats['total'] ?? 0) + 1;
        $stats['by_type'][$jenis] = ($stats['by_type'][$jenis] ?? 0) + 1;
        $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;
        $stats['bulk'] = ($stats['bulk'] ?? 0) + ($isBulk ? 1 : 0);
        $stats['success'] = ($stats['success'] ?? 0) + ($success ? 1 : 0);

        Cache::put($key, $stats, now()->addDays(7));
    }

    /**
     * Track informasi-specific metrics
     */
    protected function trackInformasiMetrics(string $judul, bool $hasAttachment, bool $success): void
    {
        $key = 'whatsapp_informasi_stats_' . date('Y-m-d');
        $stats = Cache::get($key, []);

        $stats['total'] = ($stats['total'] ?? 0) + 1;
        $stats['with_attachment'] = ($stats['with_attachment'] ?? 0) + ($hasAttachment ? 1 : 0);
        $stats['success'] = ($stats['success'] ?? 0) + ($success ? 1 : 0);

        Cache::put($key, $stats, now()->addDays(7));
    }

    /**
     * Get service health status
     */
    public function getHealthStatus(): array
    {
        $successKey = 'whatsapp_metrics_success_' . date('Y-m-d-H');
        $errorKey = 'whatsapp_metrics_error_' . date('Y-m-d-H');

        $success = Cache::get($successKey, ['count' => 0, 'avg_duration' => 0]);
        $errors = Cache::get($errorKey, ['count' => 0]);

        $total = $success['count'] + $errors['count'];
        $successRate = $total > 0 ? round(($success['count'] / $total) * 100, 2) : 100;

        return [
            'status' => $successRate >= 95 ? 'healthy' : ($successRate >= 80 ? 'degraded' : 'unhealthy'),
            'success_rate' => $successRate,
            'avg_response_time' => $success['avg_duration'],
            'total_requests' => $total,
            'error_count' => $errors['count'],
            'is_rate_limited' => $this->isRateLimited(),
        ];
    }
}
