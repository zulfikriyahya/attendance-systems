<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $endpoint;

    protected int $timeout;

    protected bool $loggingEnabled;

    protected array $config;

    public function __construct()
    {
        $this->config = config('whatsapp', []);
        $this->endpoint = $this->config['endpoint'] ?? config('whatsapp.endpoint');
        $this->timeout = $this->config['timeout'] ?? config('whatsapp.timeout');
        $this->loggingEnabled = $this->config['logging']['enabled'] ?? true;
    }

    /**
     * Send WhatsApp message with enhanced error handling and logging
     */
    public function send(string $nomor, string $pesan, ?string $filePath = null, string $type = 'presensi'): array
    {
        $startTime = microtime(true);
        $nomor = $this->normalizeNumber($nomor);

        // Validation
        if (! $this->isValidNumber($nomor)) {
            return $this->buildErrorResponse('Invalid phone number format', $nomor);
        }

        // NEW: Circuit Breaker Check
        if (! $this->checkCircuitBreaker()) {
            return $this->buildErrorResponse('Service temporarily unavailable due to high error rate', $nomor);
        }

        // UPDATED: Rate Limit dengan nomor parameter
        if (! $this->handleRateLimit($type, $nomor)) {
            return $this->buildErrorResponse('Rate limit exceeded', $nomor);
        }

        try {
            $postFields = [
                'message' => $pesan,
                'number' => $nomor,
            ];

            if ($filePath && file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $maxSize = $this->config['attachments']['max_size'] ?? 16777216;

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
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($error) {
                $this->logError('CURL Error', $nomor, $error, ['duration_ms' => $duration]);
                $this->recordError(); // NEW: Record for circuit breaker

                return $this->buildErrorResponse($error, $nomor);
            }

            if ($httpCode >= 400) {
                $this->logError('HTTP Error', $nomor, "HTTP {$httpCode}", ['duration_ms' => $duration, 'response' => $response]);
                $this->recordError(); // NEW: Record for circuit breaker

                return $this->buildErrorResponse("HTTP {$httpCode}", $nomor);
            }

            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logError('JSON Decode Error', $nomor, json_last_error_msg(), ['duration_ms' => $duration, 'raw_response' => $response]);

                return $this->buildErrorResponse('Invalid JSON response', $nomor);
            }

            $this->logSuccess($nomor, $duration, $decodedResponse);
            $this->updateMetrics('success', $duration);

            return $decodedResponse ?? ['status' => true, 'message' => 'Sent successfully'];
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError('Exception', $nomor, $e->getMessage(), ['duration_ms' => $duration, 'trace' => $e->getTraceAsString()]);
            $this->recordError(); // NEW: Record for circuit breaker
            $this->updateMetrics('error', $duration);

            return $this->buildErrorResponse($e->getMessage(), $nomor);
        }
    }

    /**
     * Build consistent header & footer untuk semua pesan
     */
    protected function buildMessageFrame(string $instansi, string $templateType = 'presensi'): array
    {
        $templates = $this->config['templates'][$templateType] ?? [];
        $tahunIni = date('Y');

        $header = strtoupper(str_replace(
            '{instansi}',
            $instansi,
            $templates['header'] ?? 'PTSP {instansi}'
        ));

        $footer = strtoupper(str_replace(
            ['{tahun}', '{instansi}'],
            [$tahunIni, $instansi],
            $templates['footer'] ?? '© 2022 - {tahun} {instansi}'
        ));

        return compact('header', 'footer');
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
        $urlPresensi = config('app.url').'/admin';

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

        // USE NEW METHOD
        $frame = $this->buildMessageFrame($instansi, 'presensi');

        $pesan = <<<TEXT
        *{$frame['header']}*

        ———————————————————
        *🗃️ Presensi {$ikon}*
        ———————————————————
        Nama    : {$nama}
        Status  : *{$status}*
        Tanggal : {$tanggal}
        Waktu   : {$waktu} WIB
        ———————————————————
        Tautan  : {$urlPresensi}
        ———————————————————

        {$penutup}

        *{$frame['footer']}*
        TEXT;

        $type = $isBulk ? 'bulk' : 'presensi';
        $result = $this->send($nomor, $pesan, null, $type);

        $this->trackMetrics('presensi', [
            'by_type' => [$jenis => 1],
            'by_status' => [$status => 1],
            'bulk' => $isBulk ? 1 : 0,
        ], $result['status'] ?? false);

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
        $urlInformasi = config('app.url').'/admin/informasi';

        $maxLength = $templates['max_content_length'] ?? 300;
        $isiSingkat = strlen($isi) > $maxLength
            ? substr($isi, 0, $maxLength).'... (Baca selengkapnya.)'
            : $isi;

        $userType = $isSiswa ? 'siswa' : 'pegawai';
        $title = strtoupper($judul);
        $greeting = $templates['greetings'][$userType]
            ?? ($isSiswa ? 'Kepada Bapak/Ibu/Wali Siswa yang terhormat,' : 'Kepada Bapak/Ibu yang terhormat,');

        $closing = $templates['closing'][$userType]
            ?? ($isSiswa ? 'Terima kasih atas perhatiannya. Tetap semangat belajar!' : 'Terima kasih atas perhatian dan kerjasamanya.');

        // USE NEW METHOD
        $frame = $this->buildMessageFrame($instansi, 'informasi');

        $pesan = <<<TEXT
        *{$frame['header']}*

        ———————————————————
        📢 *INFORMASI TERBARU*
        _{$tanggalFormatted}_
        ———————————————————
        {$greeting}

        *{$title}*

        {$isiSingkat}
        ———————————————————
        Tautan: {$urlInformasi}
        ———————————————————

        {$closing}

        *{$frame['footer']}*
        TEXT;

        $result = $this->send($nomor, $pesan, null, 'informasi');

        if ($result['status'] && $lampiran) {
            $result = $this->sendAttachment($nomor, $lampiran, $result);
        }

        $this->trackMetrics('informasi', [
            'with_attachment' => $lampiran !== null ? 1 : 0,
        ], $result['status'] ?? false);

        return $result;
    }

    /**
     * Send attachment with proper validation and error handling
     */
    protected function sendAttachment(string $nomor, string $lampiran, array $mainResult): array
    {
        $filePath = storage_path('app/public/'.$lampiran);
        Log::info('Sending attachment', [
            'file_path' => $filePath,
            'file_exists' => file_exists($filePath),
            'nomor' => $nomor,
        ]);

        if (! file_exists($filePath)) {
            $this->logError('Attachment Not Found', $nomor, "File not found: {$lampiran}");

            return array_merge($mainResult, ['attachment_error' => 'File not found']);
        }

        // Validate file type
        $extension = strtolower(pathinfo($lampiran, PATHINFO_EXTENSION));
        $allowedTypes = $this->config['attachments']['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'pdf'];

        if (! in_array($extension, $allowedTypes)) {
            $this->logError('Invalid File Type', $nomor, "File type not allowed: {$extension}");

            return array_merge($mainResult, ['attachment_error' => 'File type not allowed']);
        }

        // Delay sudah di-handle oleh applyRateLimit() di method send()
        $namaFile = basename($lampiran);
        $attachmentResult = $this->send($nomor, "Lampiran: {$namaFile}", $filePath, 'informasi');

        return array_merge($mainResult, ['attachment_result' => $attachmentResult]);
    }

    /**
     * Send pengajuan kartu notification
     */
    public function sendPengajuanKartu(
        string $nomor,
        string $nama,
        string $nomorPengajuan,
        string $instansi,
        string $pengajuanId,
        string $notificationType,
        ?float $biaya = null
    ): array {
        $url = config('app.url').'/admin/pengajuan-kartu/'.$pengajuanId;

        // USE NEW METHOD - but untuk pengajuan kartu, kita buat inline karena tidak ada di templates
        $tahunIni = date('Y');
        $header = "PTSP {$instansi}";
        $footer = "© 2022 - {$tahunIni} {$instansi}";

        if ($notificationType === 'selesai') {
            $biayaFormatted = number_format($biaya, 0, ',', '.');
            $pesan = <<<TEXT
        *{$header}*

        ———————————————————
        🪪 *Kartu Siap Diambil di Ruang PTSP*
        ———————————————————
        Halo {$nama},
        Pengajuan kartu Anda dengan nomor *{$nomorPengajuan}* telah selesai diproses.
        🏢 Silakan ambil di Ruang PTSP pada jam kerja Madrasah.
        💸 Biaya pembuatan kartu: Rp. *{$biayaFormatted}*,-

        Terima kasih! 🙏
        ———————————————————
        Tautan : {$url}
        ———————————————————

        *{$footer}*
        TEXT;
        } else {
            $pesan = <<<TEXT
        *{$header}*

        ———————————————————
        🪪 *Kartu Presensi Sedang Diproses*
        ———————————————————
        Halo {$nama},
        Pengajuan kartu Anda dengan nomor *{$nomorPengajuan}* sedang diproses.
        Mohon menunggu kabar selanjutnya.

        Terima kasih! 🙏
        ———————————————————
        Tautan : {$url}
        ———————————————————

        *{$footer}*
        TEXT;
        }

        $result = $this->send($nomor, $pesan, null, 'pengajuan_kartu');
        $this->trackMetrics('pengajuan_kartu', [
            'by_type' => [$notificationType => 1],
        ], $result['status'] ?? false);

        return $result;
    }

    /**
     * Multi-tier rate limit handler
     *
     * @param  string  $type  Type of message (presensi, bulk, informasi)
     * @param  string  $nomor  Phone number for per-number limiting
     */
    protected function handleRateLimit(string $type = 'presensi', string $nomor = ''): bool
    {
        $now = now();

        // ============================================================
        // TIER 1: Check Global Daily Limit
        // ============================================================
        $dailyKey = 'whatsapp_global_daily_'.$now->format('Y-m-d');
        $dailyLimit = $this->config['rate_limits']['global']['daily'] ?? 5000;
        $dailyCount = Cache::get($dailyKey, 0);

        if ($dailyCount >= $dailyLimit) {
            $this->logError('Rate Limit - Global Daily', 'system',
                "Global daily limit exceeded: {$dailyCount}/{$dailyLimit}",
                ['date' => $now->format('Y-m-d')]
            );

            return false;
        }

        // ============================================================
        // TIER 2: Check Global Hourly Limit
        // ============================================================
        $hourlyKey = 'whatsapp_global_hourly_'.$now->format('Y-m-d-H');
        $hourlyLimit = $this->config['rate_limits']['global']['hourly'] ?? 500;
        $hourlyCount = Cache::get($hourlyKey, 0);

        if ($hourlyCount >= $hourlyLimit) {
            $this->logError('Rate Limit - Global Hourly', 'system',
                "Global hourly limit exceeded: {$hourlyCount}/{$hourlyLimit}",
                ['hour' => $now->format('Y-m-d H:00')]
            );

            return false;
        }

        // ============================================================
        // TIER 3: Check Type-Specific Daily Limit
        // ============================================================
        $typeDailyKey = "whatsapp_{$type}_daily_".$now->format('Y-m-d');
        $typeDailyLimit = $this->config['rate_limits'][$type]['messages_per_day'] ?? 3000;
        $typeDailyCount = Cache::get($typeDailyKey, 0);

        if ($typeDailyCount >= $typeDailyLimit) {
            $this->logError('Rate Limit - Type Daily', 'system',
                "Type '{$type}' daily limit exceeded: {$typeDailyCount}/{$typeDailyLimit}",
                ['type' => $type, 'date' => $now->format('Y-m-d')]
            );

            return false;
        }

        // ============================================================
        // TIER 4: Check Type-Specific Hourly Limit
        // ============================================================
        $typeHourlyKey = "whatsapp_{$type}_hourly_".$now->format('Y-m-d-H');
        $typeHourlyLimit = $this->config['rate_limits'][$type]['messages_per_hour'] ?? 300;
        $typeHourlyCount = Cache::get($typeHourlyKey, 0);

        if ($typeHourlyCount >= $typeHourlyLimit) {
            $this->logError('Rate Limit - Type Hourly', 'system',
                "Type '{$type}' hourly limit exceeded: {$typeHourlyCount}/{$typeHourlyLimit}",
                ['type' => $type, 'hour' => $now->format('Y-m-d H:00')]
            );

            return false;
        }

        // ============================================================
        // TIER 5: Check Per-Minute Limit (existing)
        // ============================================================
        $minuteKey = "whatsapp_rate_limit_{$type}_".$now->format('Y-m-d-H-i');
        $minuteLimit = $this->config['rate_limits'][$type]['messages_per_minute'] ?? 20;
        $minuteCount = Cache::get($minuteKey, 0);

        if ($minuteCount >= $minuteLimit) {
            $this->logError('Rate Limit - Per Minute', 'system',
                "Type '{$type}' per-minute limit exceeded: {$minuteCount}/{$minuteLimit}",
                ['type' => $type, 'minute' => $now->format('Y-m-d H:i')]
            );

            return false;
        }

        // ============================================================
        // TIER 6: Check Per-Number Limit (if nomor provided)
        // ============================================================
        if (! empty($nomor)) {
            // Per-number hourly check
            $numberHourlyKey = "whatsapp_number_{$nomor}_hourly_".$now->format('Y-m-d-H');
            $numberHourlyLimit = $this->config['rate_limits']['per_number']['hourly'] ?? 2;
            $numberHourlyCount = Cache::get($numberHourlyKey, 0);

            if ($numberHourlyCount >= $numberHourlyLimit) {
                $this->logError('Rate Limit - Per Number Hourly', $nomor,
                    "Per-number hourly limit exceeded: {$numberHourlyCount}/{$numberHourlyLimit}",
                    ['hour' => $now->format('Y-m-d H:00')]
                );

                return false;
            }

            // Per-number daily check
            $numberDailyKey = "whatsapp_number_{$nomor}_daily_".$now->format('Y-m-d');
            $numberDailyLimit = $this->config['rate_limits']['per_number']['daily'] ?? 5;
            $numberDailyCount = Cache::get($numberDailyKey, 0);

            if ($numberDailyCount >= $numberDailyLimit) {
                $this->logError('Rate Limit - Per Number Daily', $nomor,
                    "Per-number daily limit exceeded: {$numberDailyCount}/{$numberDailyLimit}",
                    ['date' => $now->format('Y-m-d')]
                );

                return false;
            }
        }

        // ============================================================
        // Apply Random Delay (natural behavior)
        // ============================================================
        $ratePerMinute = $minuteLimit;
        $baseDelayMs = (int) (60000 / $ratePerMinute);
        $variation = $baseDelayMs * 0.2; // ±20% variation
        $minDelay = (int) ($baseDelayMs - $variation);
        $maxDelay = (int) ($baseDelayMs + $variation);
        $randomDelayMs = random_int($minDelay, $maxDelay);

        usleep($randomDelayMs * 1000); // Convert to microseconds

        // ============================================================
        // Increment All Counters
        // ============================================================

        // Global counters
        Cache::add($dailyKey, 0, $now->copy()->endOfDay()->addMinutes(5));
        Cache::increment($dailyKey, 1);

        Cache::add($hourlyKey, 0, $now->copy()->endOfHour()->addMinutes(5));
        Cache::increment($hourlyKey, 1);

        // Type-specific counters
        Cache::add($typeDailyKey, 0, $now->copy()->endOfDay()->addMinutes(5));
        Cache::increment($typeDailyKey, 1);

        Cache::add($typeHourlyKey, 0, $now->copy()->endOfHour()->addMinutes(5));
        Cache::increment($typeHourlyKey, 1);

        Cache::add($minuteKey, 0, now()->addMinutes(2));
        Cache::increment($minuteKey, 1);

        // Per-number counters (if applicable)
        if (! empty($nomor)) {
            Cache::add($numberHourlyKey, 0, $now->copy()->endOfHour()->addMinutes(5));
            Cache::increment($numberHourlyKey, 1);

            Cache::add($numberDailyKey, 0, $now->copy()->endOfDay()->addMinutes(5));
            Cache::increment($numberDailyKey, 1);
        }

        return true;
    }

    /**
     * Circuit Breaker - Stop sementara jika terlalu banyak error
     *
     * @return bool True if safe to proceed, false if circuit is open
     */
    protected function checkCircuitBreaker(): bool
    {
        if (! ($this->config['circuit_breaker']['enabled'] ?? true)) {
            return true; // Circuit breaker disabled
        }

        $now = now();
        $errorKey = 'whatsapp_errors_'.$now->format('Y-m-d-H');
        $errorThreshold = $this->config['circuit_breaker']['error_threshold'] ?? 50;
        $errorCount = Cache::get($errorKey, 0);

        // Check if circuit is open
        if ($errorCount >= $errorThreshold) {
            $circuitKey = 'whatsapp_circuit_breaker_open';

            // Check if already in cooldown
            if (Cache::has($circuitKey)) {
                $this->logError('Circuit Breaker', 'system',
                    "Circuit breaker is OPEN. Service paused. Error count: {$errorCount}/{$errorThreshold}"
                );

                return false;
            }

            // Open circuit for cooldown period
            $cooldownMinutes = $this->config['circuit_breaker']['cooldown_minutes'] ?? 30;
            Cache::put($circuitKey, true, now()->addMinutes($cooldownMinutes));

            $this->logError('Circuit Breaker', 'system',
                "Circuit breaker ACTIVATED. Too many errors: {$errorCount}/{$errorThreshold}. Cooldown: {$cooldownMinutes} minutes",
                ['cooldown_until' => now()->addMinutes($cooldownMinutes)->toIso8601String()]
            );

            return false;
        }

        return true;
    }

    /**
     * Record error for circuit breaker
     * Call this whenever there's an error
     */
    protected function recordError(): void
    {
        if (! ($this->config['circuit_breaker']['enabled'] ?? true)) {
            return;
        }

        $errorKey = 'whatsapp_errors_'.now()->format('Y-m-d-H');

        Cache::add($errorKey, 0, now()->endOfHour()->addMinutes(5));
        Cache::increment($errorKey, 1);
    }

    /**
     * Check if any rate limit is active (for monitoring)
     *
     * @return array Status of all rate limits
     */
    protected function checkRateLimitStatus(string $type = 'presensi'): array
    {
        $now = now();

        return [
            'global_daily' => [
                'current' => Cache::get('whatsapp_global_daily_'.$now->format('Y-m-d'), 0),
                'limit' => $this->config['rate_limits']['global']['daily'] ?? 5000,
            ],
            'global_hourly' => [
                'current' => Cache::get('whatsapp_global_hourly_'.$now->format('Y-m-d-H'), 0),
                'limit' => $this->config['rate_limits']['global']['hourly'] ?? 500,
            ],
            'type_hourly' => [
                'current' => Cache::get("whatsapp_{$type}_hourly_".$now->format('Y-m-d-H'), 0),
                'limit' => $this->config['rate_limits'][$type]['messages_per_hour'] ?? 300,
            ],
            'type_minute' => [
                'current' => Cache::get("whatsapp_rate_limit_{$type}_".$now->format('Y-m-d-H-i'), 0),
                'limit' => $this->config['rate_limits'][$type]['messages_per_minute'] ?? 20,
            ],
            'circuit_breaker' => [
                'errors' => Cache::get('whatsapp_errors_'.$now->format('Y-m-d-H'), 0),
                'threshold' => $this->config['circuit_breaker']['error_threshold'] ?? 50,
                'is_open' => Cache::has('whatsapp_circuit_breaker_open'),
            ],
        ];
    }

    /**
     * Enhanced phone number validation
     */
    protected function isValidNumber(string $nomor): bool
    {
        if (! $this->config['phone']['validation']['enabled'] ?? true) {
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
            return $prefix.substr($nomor, strlen($countryCode));
        }
        if (str_starts_with($nomor, '8')) {
            return $prefix.substr($nomor, 1);
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
                    'reason' => 'Invalid format',
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
                'success_rate' => count($nomors) > 0 ? round((count($valid) / count($nomors)) * 100, 2) : 0,
            ],
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
            'context' => $context,
        ];
    }

    /**
     * Enhanced logging methods
     */
    protected function logSuccess(string $nomor, float $duration, array $response): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        Log::channel($this->config['logging']['channels']['success'] ?? 'single')->info('WhatsApp message sent successfully', [
            'recipient' => $nomor,
            'duration_ms' => $duration,
            'response' => $this->config['logging']['context']['include_response'] ? $response : 'hidden',
        ]);
    }

    protected function logError(string $type, string $nomor, string $message, array $context = []): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        Log::channel($this->config['logging']['channels']['error'] ?? 'single')->error("WhatsApp {$type}", array_merge([
            'recipient' => $nomor,
            'error' => $message,
        ], $context));
    }

    protected function logInfo(string $message, array $context = []): void
    {
        if (! $this->loggingEnabled) {
            return;
        }

        Log::info($message, $context);
    }

    /**
     * Update performance metrics
     */
    protected function updateMetrics(string $type, float $duration): void
    {
        $key = "whatsapp_metrics_{$type}_".date('Y-m-d-H');
        $data = Cache::get($key, ['count' => 0, 'total_duration' => 0]);

        $data['count']++;
        $data['total_duration'] += $duration;
        $data['avg_duration'] = $data['total_duration'] / $data['count'];

        Cache::put($key, $data, now()->addHours(25)); // Keep for analysis
    }

    /**
     * Generic metrics tracker untuk semua tipe pesan
     */
    protected function trackMetrics(string $category, array $incrementData, bool $success): void
    {
        $key = "whatsapp_{$category}_stats_".date('Y-m-d');
        $stats = Cache::get($key, []);

        $stats['total'] = ($stats['total'] ?? 0) + 1;
        $stats['success'] = ($stats['success'] ?? 0) + ($success ? 1 : 0);

        foreach ($incrementData as $field => $value) {
            if (is_array($value)) {
                foreach ($value as $subField => $subValue) {
                    $stats[$field][$subField] = ($stats[$field][$subField] ?? 0) + $subValue;
                }
            } else {
                $stats[$field] = ($stats[$field] ?? 0) + $value;
            }
        }

        Cache::put($key, $stats, now()->addDays(7));
    }

    /**
     * Get comprehensive service health status
     */
    public function getHealthStatus(): array
    {
        $now = now();
        $successKey = 'whatsapp_metrics_success_'.$now->format('Y-m-d-H');
        $errorKey = 'whatsapp_metrics_error_'.$now->format('Y-m-d-H');

        $success = Cache::get($successKey, ['count' => 0, 'avg_duration' => 0]);
        $errors = Cache::get($errorKey, ['count' => 0]);

        $total = $success['count'] + $errors['count'];
        $successRate = $total > 0 ? round(($success['count'] / $total) * 100, 2) : 100;

        // Get rate limit status
        $rateLimits = $this->checkRateLimitStatus('presensi');

        // Calculate percentage used for each limit
        $globalDailyUsage = $rateLimits['global_daily']['limit'] > 0
            ? round(($rateLimits['global_daily']['current'] / $rateLimits['global_daily']['limit']) * 100, 2)
            : 0;

        $globalHourlyUsage = $rateLimits['global_hourly']['limit'] > 0
            ? round(($rateLimits['global_hourly']['current'] / $rateLimits['global_hourly']['limit']) * 100, 2)
            : 0;

        // Determine overall status
        $status = 'healthy';
        if ($successRate < 80 || $rateLimits['circuit_breaker']['is_open']) {
            $status = 'unhealthy';
        } elseif ($successRate < 95 || $globalHourlyUsage > 80) {
            $status = 'degraded';
        }

        return [
            'status' => $status,
            'timestamp' => $now->toIso8601String(),

            // Performance metrics
            'performance' => [
                'success_rate' => $successRate,
                'avg_response_time' => $success['avg_duration'],
                'total_requests' => $total,
                'error_count' => $errors['count'],
            ],

            // Rate limit status
            'rate_limits' => [
                'global_daily' => [
                    'used' => $rateLimits['global_daily']['current'],
                    'limit' => $rateLimits['global_daily']['limit'],
                    'percentage' => $globalDailyUsage,
                ],
                'global_hourly' => [
                    'used' => $rateLimits['global_hourly']['current'],
                    'limit' => $rateLimits['global_hourly']['limit'],
                    'percentage' => $globalHourlyUsage,
                ],
                'current_minute' => [
                    'used' => $rateLimits['type_minute']['current'],
                    'limit' => $rateLimits['type_minute']['limit'],
                ],
            ],

            // Circuit breaker status
            'circuit_breaker' => [
                'enabled' => $this->config['circuit_breaker']['enabled'] ?? true,
                'is_open' => $rateLimits['circuit_breaker']['is_open'],
                'error_count' => $rateLimits['circuit_breaker']['errors'],
                'error_threshold' => $rateLimits['circuit_breaker']['threshold'],
            ],
        ];
    }
}
