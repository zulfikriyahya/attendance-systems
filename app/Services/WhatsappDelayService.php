<?php

// namespace App\Services;

// use Carbon\Carbon;
// use Illuminate\Support\Facades\Cache;

// class WhatsappDelayService
// {
//     /**
//      * Hitung delay untuk real-time notifications (presensi normal)
//      */
//     public function calculateRealtimeDelay(string $status): Carbon
//     {
//         $now = now();
//         $today = $now->format('Y-m-d');
//         $currentHour = $now->format('H');

//         // Cache key per jam untuk reset otomatis setiap jam
//         $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";

//         // Hitung jumlah notifikasi dalam jam ini
//         $hourlyCount = Cache::get($hourlyCacheKey, 0);

//         // Rate limit untuk real-time
//         $messagesPerMinute = 35; // Rate yang aman
//         $maxDelayMinutes = 30;   // Maksimal 30 menit

//         // Hitung slot berdasarkan urutan dalam jam ini
//         $minuteSlot = floor($hourlyCount / $messagesPerMinute);

//         // Jika sudah melewati 30 menit, reset ke awal dengan jeda kecil
//         if ($minuteSlot >= $maxDelayMinutes) {
//             $minuteSlot = $minuteSlot % $maxDelayMinutes;
//             $extraOffset = floor($hourlyCount / ($messagesPerMinute * $maxDelayMinutes)) * 60;
//         } else {
//             $extraOffset = 0;
//         }

//         // Priority system untuk status tertentu
//         $isPriority = in_array($status, ['Terlambat', 'Pulang Cepat']);

//         if ($isPriority) {
//             // Priority: delay minimal (0-2 menit)
//             $baseDelaySeconds = rand(10, 120);
//             $slotDelaySeconds = min($minuteSlot * 30, 300); // Max 5 menit untuk priority
//         } else {
//             // Normal: distribusi merata dalam 30 menit
//             $baseDelaySeconds = rand(15, 45);
//             $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
//         }

//         // Random spread untuk distribusi natural
//         $randomSpread = rand(0, 30);

//         // Total delay dalam detik
//         $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $extraOffset;

//         // Pastikan tidak melebihi 30 menit (1800 detik)
//         $maxDelaySeconds = $maxDelayMinutes * 60;
//         $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

//         // Update counter dengan expire otomatis di akhir jam
//         Cache::put($hourlyCacheKey, $hourlyCount + 1, now()->endOfHour());

//         return $now->addSeconds($totalDelaySeconds);
//     }

//     /**
//      * Hitung delay untuk bulk notifications
//      */
//     public function calculateBulkDelay(int $counter, string $type): Carbon
//     {
//         $now = now();

//         // Rate yang aman untuk bulk notification (lebih konservatif)
//         $messagesPerMinute = 20; // Lebih pelan karena ini bulk/mass notification

//         // Hitung delay berdasarkan counter
//         $minuteSlot = floor($counter / $messagesPerMinute);

//         // Base delay + slot delay
//         $baseDelaySeconds = rand(10, 30); // Delay dasar
//         $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
//         $randomSpread = rand(0, 60); // Random spread lebih besar

//         // Priority untuk different types
//         switch ($type) {
//             case 'alfa':
//                 // Alfa notification: delay normal
//                 $priorityOffset = 0;
//                 break;
//             case 'mangkir':
//             case 'bolos':
//                 // Mangkir/Bolos: delay sedikit lebih lama (bukan urgent)
//                 $priorityOffset = rand(60, 180); // 1-3 menit extra
//                 break;
//             case 'informasi':
//                 // Informasi: delay sedang
//                 $priorityOffset = rand(30, 90); // 30s-1.5min extra
//                 break;
//             default:
//                 $priorityOffset = 0;
//         }

//         $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset;

//         // Maksimal delay 2 jam untuk bulk notification
//         $maxDelaySeconds = 2 * 60 * 60; // 2 jam
//         $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

//         return $now->addSeconds($totalDelaySeconds);
//     }
// }

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class WhatsappDelayService
{
    // Rate limit yang SANGAT AMAN untuk menghindari banned
    private const SAFE_MESSAGES_PER_MINUTE = 25; // Turun dari 35 ke 25 (lebih aman)
    private const MAX_DELAY_MINUTES = 45; // Naik dari 30 ke 45 menit (lebih spread)
    private const BULK_MESSAGES_PER_MINUTE = 15; // Turun dari 20 ke 15 (lebih konservatif)
    
    // Minimum jeda antar pesan untuk avoid burst detection
    private const MIN_GAP_SECONDS = 3; // Minimum 3 detik antar pesan
    
    /**
     * Hitung delay untuk real-time notifications (presensi normal)
     * THREAD-SAFE untuk 3 workers paralel
     */
    public function calculateRealtimeDelay(string $status): Carbon
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');
        
        // ATOMIC COUNTER menggunakan Redis INCR (thread-safe)
        $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $hourlyCount = Redis::incr($hourlyCacheKey);
        
        // Set expiry jika ini counter pertama
        if ($hourlyCount === 1) {
            Redis::expireat($hourlyCacheKey, now()->endOfHour()->timestamp);
        }
        
        // CRITICAL: Rate limit lebih ketat untuk safety
        $messagesPerMinute = self::SAFE_MESSAGES_PER_MINUTE;
        $maxDelayMinutes = self::MAX_DELAY_MINUTES;
        
        // Hitung slot berdasarkan urutan dalam jam ini
        $minuteSlot = floor($hourlyCount / $messagesPerMinute);
        
        // Jika sudah melewati max delay, wrap around dengan extra offset
        if ($minuteSlot >= $maxDelayMinutes) {
            $cycle = floor($minuteSlot / $maxDelayMinutes);
            $minuteSlot = $minuteSlot % $maxDelayMinutes;
            // Extra offset per cycle (makin besar makin lama)
            $extraOffset = $cycle * 120; // 2 menit per cycle
        } else {
            $extraOffset = 0;
        }
        
        // Priority system untuk status tertentu
        $isPriority = in_array($status, ['Terlambat', 'Pulang Cepat', 'Sakit', 'Izin']);
        
        if ($isPriority) {
            // Priority: delay minimal tapi tetap ada gap
            $baseDelaySeconds = rand(30, 90); // Naik dari 10-120 ke 30-90 (lebih aman)
            $slotDelaySeconds = min($minuteSlot * 40, 600); // Max 10 menit untuk priority
        } else {
            // Normal: distribusi lebih lebar
            $baseDelaySeconds = rand(45, 90); // Naik dari 15-45 ke 45-90
            $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        }
        
        // CRITICAL: Random spread lebih besar untuk avoid pattern detection
        $randomSpread = rand(10, 60); // Naik dari 0-30 ke 10-60
        
        // ANTI-BURST: Tambahkan jeda minimum berdasarkan position dalam antrian
        // Setiap worker punya "lane" sendiri untuk menghindari collision
        $workerLane = ($hourlyCount % 3); // 0, 1, atau 2 (untuk 3 workers)
        $laneOffset = $workerLane * self::MIN_GAP_SECONDS; // 0s, 3s, 6s
        
        // Total delay dalam detik
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $extraOffset + $laneOffset;
        
        // Pastikan tidak melebihi max delay
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);
        
        // ADDITIONAL SAFETY: Tambah micro-jitter untuk unique timestamp
        $microJitter = ($hourlyCount % 100) / 100; // 0-0.99 detik
        
        // WAVE PATTERN: Hindari burst di awal jam
        if ($now->minute < 5 && $hourlyCount > 50) {
            // Jika terlalu banyak pesan di 5 menit pertama, tambah delay
            $totalDelaySeconds += 180; // Tambah 3 menit
        }
        
        // Log untuk monitoring (optional)
        if (config('whatsapp.logging_enabled', true)) {
            Log::debug('WhatsApp delay calculated', [
                'hourly_count' => $hourlyCount,
                'delay_seconds' => $totalDelaySeconds,
                'status' => $status,
                'worker_lane' => $workerLane,
            ]);
        }
        
        return $now->addSeconds($totalDelaySeconds)->addMicroseconds($microJitter * 1000000);
    }
    
    /**
     * Hitung delay untuk bulk notifications
     * EXTRA SAFE untuk mass sending
     */
    public function calculateBulkDelay(int $counter, string $type): Carbon
    {
        $now = now();
        $today = $now->format('Y-m-d');
        
        // ATOMIC COUNTER per type
        $bulkCacheKey = "whatsapp_bulk_{$type}_{$today}";
        $dailyCount = Redis::incr($bulkCacheKey);
        
        // Set expiry di akhir hari
        if ($dailyCount === 1) {
            Redis::expireat($bulkCacheKey, now()->endOfDay()->timestamp);
        }
        
        // Rate yang SANGAT AMAN untuk bulk
        $messagesPerMinute = self::BULK_MESSAGES_PER_MINUTE;
        
        // Hitung delay berdasarkan GLOBAL counter (bukan parameter)
        $minuteSlot = floor($dailyCount / $messagesPerMinute);
        
        // Base delay lebih besar untuk bulk
        $baseDelaySeconds = rand(30, 60); // Naik dari 10-30 ke 30-60
        $slotDelaySeconds = $minuteSlot * 60; // 1 menit per slot
        
        // Random spread LEBIH BESAR untuk bulk
        $randomSpread = rand(30, 120); // Naik dari 0-90 ke 30-120
        
        // ANTI-BURST untuk bulk: Tambah worker lane offset
        $workerLane = ($dailyCount % 3);
        $laneOffset = $workerLane * 5; // 0s, 5s, 10s
        
        // Priority berdasarkan type
        switch ($type) {
            case 'alfa':
                $priorityOffset = rand(60, 180); // 1-3 menit
                $maxHours = 6; // Max 6 jam delay
                break;
                
            case 'mangkir':
            case 'bolos':
                $priorityOffset = rand(300, 600); // 5-10 menit (lebih lama)
                $maxHours = 8; // Max 8 jam delay
                break;
                
            case 'informasi':
                $priorityOffset = rand(120, 300); // 2-5 menit
                $maxHours = 6; // Max 6 jam delay
                break;
                
            case 'pengumuman':
                $priorityOffset = rand(600, 1800); // 10-30 menit (sangat lama)
                $maxHours = 24; // Max 24 jam delay
                break;
                
            default:
                $priorityOffset = rand(60, 180);
                $maxHours = 4;
        }
        
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset + $laneOffset;
        
        // Maksimal delay berdasarkan type
        $maxDelaySeconds = $maxHours * 60 * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);
        
        // CRITICAL: WAVE PATTERN - Hindari jam sibuk dan jam tidur
        $targetTime = $now->addSeconds($totalDelaySeconds);
        $targetHour = $targetTime->hour;
        
        // Hindari jam sibuk pagi (07:00-09:00)
        if (in_array($targetHour, [7, 8])) {
            $totalDelaySeconds += 2 * 60 * 60; // Tambah 2 jam
        }
        
        // Hindari jam makan siang (12:00-13:00)
        if ($targetHour === 12) {
            $totalDelaySeconds += 1 * 60 * 60; // Tambah 1 jam
        }
        
        // CRITICAL: Hindari jam tidur (22:00-05:00)
        if ($targetHour >= 22 || $targetHour < 5) {
            // Tunda sampai jam 06:00 pagi
            $nextMorning = $now->copy()->addDay()->setTime(6, 0, 0);
            $totalDelaySeconds = $now->diffInSeconds($nextMorning) + rand(0, 1800); // + 0-30 menit
        }
        
        // Log untuk monitoring
        if (config('whatsapp.logging_enabled', true)) {
            Log::debug('Bulk WhatsApp delay calculated', [
                'daily_count' => $dailyCount,
                'delay_seconds' => $totalDelaySeconds,
                'type' => $type,
                'worker_lane' => $workerLane,
                'target_time' => $now->addSeconds($totalDelaySeconds)->format('Y-m-d H:i:s'),
            ]);
        }
        
        return $now->addSeconds($totalDelaySeconds);
    }
    
    /**
     * Check apakah saat ini jam sibuk (untuk additional safety)
     */
    public function isPeakHour(): bool
    {
        $hour = now()->hour;
        return in_array($hour, [7, 8, 12, 13, 17, 18]);
    }
    
    /**
     * Get safe delay untuk manual dispatch
     * Digunakan jika ingin dispatch langsung dengan safety check
     */
    public function getSafeDelay(): Carbon
    {
        $now = now();
        $hour = $now->hour;
        
        // Jika jam sibuk, tambah delay minimum
        if ($this->isPeakHour()) {
            $minDelay = rand(300, 600); // 5-10 menit
        } else {
            $minDelay = rand(60, 180); // 1-3 menit
        }
        
        return $now->addSeconds($minDelay);
    }
    
    /**
     * Get current rate limit status (untuk monitoring)
     */
    public function getRateLimitStatus(): array
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');
        
        $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $hourlyCount = (int) Redis::get($hourlyCacheKey) ?? 0;
        
        $messagesPerMinute = self::SAFE_MESSAGES_PER_MINUTE;
        $currentMinuteSlot = floor($hourlyCount / $messagesPerMinute);
        $estimatedDelay = min($currentMinuteSlot, self::MAX_DELAY_MINUTES);
        
        return [
            'hourly_count' => $hourlyCount,
            'current_rate' => $hourlyCount > 0 ? round($hourlyCount / max($now->minute, 1), 2) : 0,
            'estimated_delay_minutes' => $estimatedDelay,
            'status' => $estimatedDelay < 15 ? 'green' : ($estimatedDelay < 30 ? 'yellow' : 'red'),
            'capacity_percentage' => min(round(($hourlyCount / ($messagesPerMinute * 60)) * 100, 2), 100),
            'is_peak_hour' => $this->isPeakHour(),
            'safe_rate_limit' => $messagesPerMinute,
        ];
    }
    
    /**
     * Reset counter (untuk testing atau manual reset)
     */
    public function resetCounters(): void
    {
        $patterns = [
            "whatsapp_hourly_*",
            "whatsapp_bulk_*",
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }
        
        Log::info('WhatsApp counters reset');
    }
    
    /**
     * Get queue health metrics
     */
    public function getQueueHealth(): array
    {
        $today = now()->format('Y-m-d');
        
        // Aggregate semua counters hari ini
        $hourlyPattern = "whatsapp_hourly_{$today}*";
        $hourlyKeys = Redis::keys($hourlyPattern);
        
        $totalToday = 0;
        foreach ($hourlyKeys as $key) {
            $totalToday += (int) Redis::get($key);
        }
        
        $currentHour = max(now()->hour, 1);
        $averagePerHour = round($totalToday / $currentHour, 2);
        
        return [
            'total_today' => $totalToday,
            'average_per_hour' => $averagePerHour,
            'projection_daily' => round($averagePerHour * 24, 0),
            'workers' => 3,
            'safe_rate' => self::SAFE_MESSAGES_PER_MINUTE,
        ];
    }
}
