<?php

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
        $messagesPerMinute = 35; // Rate yang aman
        $maxDelayMinutes = 30;   // Maksimal 30 menit

        // Hitung slot berdasarkan urutan dalam jam ini
        $minuteSlot = floor($hourlyCount / $messagesPerMinute);

        // Jika sudah melewati 30 menit, reset ke awal dengan jeda kecil
        if ($minuteSlot >= $maxDelayMinutes) {
            $minuteSlot = $minuteSlot % $maxDelayMinutes;
            $extraOffset = floor($hourlyCount / ($messagesPerMinute * $maxDelayMinutes)) * 60;
        } else {
            $extraOffset = 0;
        }

        // Priority system untuk status tertentu
        $isPriority = in_array($status, ['Terlambat', 'Pulang Cepat']);

        if ($isPriority) {
            // Priority: delay minimal (0-2 menit)
            $baseDelaySeconds = rand(10, 120);
            $slotDelaySeconds = min($minuteSlot * 30, 300); // Max 5 menit untuk priority
        } else {
            // Normal: distribusi merata dalam 30 menit
            $baseDelaySeconds = rand(15, 45);
            $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        }

        // Random spread untuk distribusi natural
        $randomSpread = rand(0, 30);

        // Total delay dalam detik
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $extraOffset;

        // Pastikan tidak melebihi 30 menit (1800 detik)
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        // Update counter dengan expire otomatis di akhir jam
        Cache::put($hourlyCacheKey, $hourlyCount + 1, now()->endOfHour());

        return $now->addSeconds($totalDelaySeconds);
    }

    /**
     * Hitung delay untuk bulk notifications
     */
    public function calculateBulkDelay(int $counter, string $type): Carbon
    {
        $now = now();

        // Rate yang aman untuk bulk notification (lebih konservatif)
        $messagesPerMinute = 20; // Lebih pelan karena ini bulk/mass notification

        // Hitung delay berdasarkan counter
        $minuteSlot = floor($counter / $messagesPerMinute);

        // Base delay + slot delay
        $baseDelaySeconds = rand(10, 30); // Delay dasar
        $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        $randomSpread = rand(0, 60); // Random spread lebih besar

        // Priority untuk different types
        switch ($type) {
            case 'alfa':
                // Alfa notification: delay normal
                $priorityOffset = 0;
                break;
            case 'mangkir':
            case 'bolos':
                // Mangkir/Bolos: delay sedikit lebih lama (bukan urgent)
                $priorityOffset = rand(60, 180); // 1-3 menit extra
                break;
            case 'informasi':
                // Informasi: delay sedang
                $priorityOffset = rand(30, 90); // 30s-1.5min extra
                break;
            default:
                $priorityOffset = 0;
        }

        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset;

        // Maksimal delay 2 jam untuk bulk notification
        $maxDelaySeconds = 2 * 60 * 60; // 2 jam
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        return $now->addSeconds($totalDelaySeconds);
    }
}