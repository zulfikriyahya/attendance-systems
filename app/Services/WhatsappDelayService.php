<?php
// Services/WhatsappDelayService.php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WhatsappDelayService
{
    /**
     * Hitung delay untuk real-time notifications (presensi normal)
     */
    public function calculateRealtimeDelay(string $status): Carbon
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');

        // Cache key per jam untuk reset otomatis setiap jam
        $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";

        // Hitung jumlah notifikasi dalam jam ini
        $hourlyCount = Cache::get($hourlyCacheKey, 0);

        // Rate limit untuk real-time
        $messagesPerMinute = config('whatsapp.rate_limits.presensi.messages_per_minute', 35);
        $maxDelayMinutes = config('whatsapp.rate_limits.presensi.max_delay_minutes', 30);
        $priorityStatuses = config('whatsapp.rate_limits.presensi.priority_statuses', ['Terlambat', 'Pulang Cepat']);

        // Hitung slot berdasarkan urutan dalam jam ini
        $minuteSlot = floor($hourlyCount / $messagesPerMinute);

        // Jika sudah melewati max delay, reset ke awal dengan jeda kecil
        if ($minuteSlot >= $maxDelayMinutes) {
            $minuteSlot = $minuteSlot % $maxDelayMinutes;
            $extraOffset = floor($hourlyCount / ($messagesPerMinute * $maxDelayMinutes)) * 60;
        } else {
            $extraOffset = 0;
        }

        // Priority system untuk status tertentu
        $isPriority = in_array($status, $priorityStatuses);

        if ($isPriority) {
            // Priority: delay minimal (0-2 menit)
            $baseDelaySeconds = rand(10, 120);
            $slotDelaySeconds = min($minuteSlot * 30, 300); // Max 5 menit untuk priority
        } else {
            // Normal: distribusi merata dalam max delay
            $baseDelaySeconds = rand(15, 45);
            $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        }

        // Random spread untuk distribusi natural
        $randomSpread = rand(0, 30);

        // Total delay dalam detik
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $extraOffset;

        // Pastikan tidak melebihi max delay
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        // Update counter dengan expire otomatis di akhir jam
        $expiresAt = $now->copy()->endOfHour()->addMinutes(5); // Tambah 5 menit buffer
        Cache::put($hourlyCacheKey, $hourlyCount + 1, $expiresAt);

        return $now->addSeconds($totalDelaySeconds);
    }

    /**
     * Hitung delay untuk bulk notifications
     */
    public function calculateBulkDelay(int $counter, string $type): Carbon
    {
        $now = now();
        $config = config("whatsapp.rate_limits.bulk.types.{$type}");

        // Default config jika type tidak ditemukan
        if (!$config) {
            $config = [
                'priority' => 3,
                'extra_delay' => [60, 180]
            ];
        }

        // Rate yang aman untuk bulk notification
        $messagesPerMinute = config('whatsapp.rate_limits.bulk.messages_per_minute', 20);

        // Hitung delay berdasarkan counter
        $minuteSlot = floor($counter / $messagesPerMinute);

        // Base delay + slot delay
        $baseDelaySeconds = rand(10, 30);
        $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        $randomSpread = rand(0, 60);

        // Priority offset dari config
        $extraDelay = $config['extra_delay'] ?? [0, 0];
        $priorityOffset = is_array($extraDelay) 
            ? rand($extraDelay[0], $extraDelay[1]) 
            : $extraDelay;

        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset;

        // Maksimal delay dari config
        $maxDelayHours = config('whatsapp.rate_limits.bulk.max_delay_hours', 2);
        $maxDelaySeconds = $maxDelayHours * 60 * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        return $now->addSeconds($totalDelaySeconds);
    }

    /**
     * Hitung delay untuk informasi broadcast
     */
    public function calculateInformasiDelay(int $counter): Carbon
    {
        $now = now();

        // Ambil config informasi
        $messagesPerMinute = config('whatsapp.rate_limits.informasi.messages_per_minute', 25);
        $maxDelayMinutes = config('whatsapp.rate_limits.informasi.max_delay_minutes', 60);
        $extraDelay = config('whatsapp.rate_limits.informasi.extra_delay', [30, 90]);

        // Hitung delay berdasarkan counter
        $minuteSlot = floor($counter / $messagesPerMinute);

        // Base delay + slot delay
        $baseDelaySeconds = rand(10, 30);
        $slotDelaySeconds = $minuteSlot * 60;
        $randomSpread = rand(0, 60);

        // Extra delay dari config
        $priorityOffset = is_array($extraDelay) 
            ? rand($extraDelay[0], $extraDelay[1]) 
            : $extraDelay;

        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset;

        // Maksimal delay
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        return $now->addSeconds($totalDelaySeconds);
    }

    /**
     * Cleanup expired cache entries (optional, bisa dijadwalkan via command)
     */
    public function cleanupExpiredCache(): int
    {
        $pattern = "whatsapp_hourly_*";
        $deleted = 0;
        
        // Note: Ini simplified version, untuk production gunakan Redis SCAN
        // atau jadwalkan cleanup via Laravel command
        
        return $deleted;
    }
}