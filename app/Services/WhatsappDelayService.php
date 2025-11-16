<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WhatsappDelayService
{
    /**
     * Base delay calculation untuk semua tipe
     */
    protected function calculateBaseDelay(
        int $counter,
        int $messagesPerMinute,
        int $maxDelayMinutes,
        array $extraConfig = []
    ): Carbon {
        $now = now();

        // Hitung slot berdasarkan urutan
        $minuteSlot = floor($counter / $messagesPerMinute);

        // Jika sudah melewati max delay, reset dengan offset
        if ($minuteSlot >= $maxDelayMinutes) {
            $minuteSlot = $minuteSlot % $maxDelayMinutes;
            $extraOffset = floor($counter / ($messagesPerMinute * $maxDelayMinutes)) * 60;
        } else {
            $extraOffset = 0;
        }

        // Base components
        $baseDelaySeconds = rand(
            $extraConfig['base_min'] ?? 10,
            $extraConfig['base_max'] ?? 45
        );

        $slotDelaySeconds = $minuteSlot * 60;
        $randomSpread = rand(0, $extraConfig['spread'] ?? 60);

        // Priority/extra delay
        $priorityOffset = 0;
        if (isset($extraConfig['extra_delay'])) {
            $extra = $extraConfig['extra_delay'];
            $priorityOffset = is_array($extra) ? rand($extra[0], $extra[1]) : $extra;
        }

        // Total delay
        $totalDelaySeconds = $baseDelaySeconds + $slotDelaySeconds + $randomSpread + $priorityOffset + $extraOffset;

        // Cap to max
        $maxDelaySeconds = $maxDelayMinutes * 60;
        $totalDelaySeconds = min($totalDelaySeconds, $maxDelaySeconds);

        return $now->addSeconds($totalDelaySeconds);
    }

    /**
     * Hitung delay untuk real-time notifications (presensi normal)
     */
    public function calculateRealtimeDelay(string $status): Carbon
    {
        $now = now();
        $today = $now->format('Y-m-d');
        $currentHour = $now->format('H');

        // Cache key per jam
        $hourlyCacheKey = "whatsapp_hourly_{$today}_{$currentHour}";
        $hourlyCount = Cache::get($hourlyCacheKey, 0);

        // Config
        $messagesPerMinute = config('whatsapp.rate_limits.presensi.messages_per_minute', 35);
        $maxDelayMinutes = config('whatsapp.rate_limits.presensi.max_delay_minutes', 30);
        $priorityStatuses = config('whatsapp.rate_limits.presensi.priority_statuses', ['Terlambat', 'Pulang Cepat']);

        // Priority system
        $isPriority = in_array($status, $priorityStatuses);

        $extraConfig = $isPriority ? [
            'base_min' => 10,
            'base_max' => 120,
            'spread' => 30,
            'extra_delay' => 0,
        ] : [
            'base_min' => 15,
            'base_max' => 45,
            'spread' => 30,
            'extra_delay' => 0,
        ];

        // Calculate delay
        $delay = $this->calculateBaseDelay(
            $hourlyCount,
            $messagesPerMinute,
            $maxDelayMinutes,
            $extraConfig
        );

        // Update counter
        $expiresAt = $now->copy()->endOfHour()->addMinutes(5);
        Cache::put($hourlyCacheKey, $hourlyCount + 1, $expiresAt);

        return $delay;
    }

    /**
     * Hitung delay untuk bulk notifications
     */
    public function calculateBulkDelay(int $counter, string $type): Carbon
    {
        $config = config("whatsapp.rate_limits.bulk.types.{$type}", [
            'priority' => 3,
            'extra_delay' => [60, 180],
        ]);

        $messagesPerMinute = config('whatsapp.rate_limits.bulk.messages_per_minute', 20);
        $maxDelayHours = config('whatsapp.rate_limits.bulk.max_delay_hours', 2);

        return $this->calculateBaseDelay(
            $counter,
            $messagesPerMinute,
            $maxDelayHours * 60, // Convert to minutes
            [
                'base_min' => 10,
                'base_max' => 30,
                'spread' => 60,
                'extra_delay' => $config['extra_delay'],
            ]
        );
    }

    /**
     * Hitung delay untuk informasi broadcast
     */
    public function calculateInformasiDelay(int $counter): Carbon
    {
        $messagesPerMinute = config('whatsapp.rate_limits.informasi.messages_per_minute', 25);
        $maxDelayMinutes = config('whatsapp.rate_limits.informasi.max_delay_minutes', 60);
        $extraDelay = config('whatsapp.rate_limits.informasi.extra_delay', [30, 90]);

        return $this->calculateBaseDelay(
            $counter,
            $messagesPerMinute,
            $maxDelayMinutes,
            [
                'base_min' => 10,
                'base_max' => 30,
                'spread' => 60,
                'extra_delay' => $extraDelay,
            ]
        );
    }

    /**
     * Cleanup expired cache entries
     */
    public function cleanupExpiredCache(): int
    {
        // Implementation for cleanup
        return 0;
    }
}
