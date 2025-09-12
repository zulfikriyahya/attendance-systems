<?php

// namespace App\Services;

// class WhatsappService
// {
//     protected string $endpoint;

//     public function __construct()
//     {
//         $this->endpoint = config('services.whatsapp.endpoint');
//     }

//     public function send(string $nomor, string $pesan, ?string $filePath = null): array
//     {
//         $nomor = $this->normalizeNumber($nomor);

//         $postFields = [
//             'message' => $pesan,
//             'number' => $nomor,
//         ];

//         if ($filePath && file_exists($filePath)) {
//             $postFields['file_dikirim'] = new \CURLFile(realpath($filePath));
//         }

//         $curl = curl_init();

//         curl_setopt_array($curl, [
//             CURLOPT_URL => $this->endpoint,
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_ENCODING => '',
//             CURLOPT_MAXREDIRS => 10,
//             CURLOPT_TIMEOUT => 15,
//             CURLOPT_FOLLOWLOCATION => true,
//             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//             CURLOPT_CUSTOMREQUEST => 'POST',
//             CURLOPT_POSTFIELDS => $postFields,
//         ]);

//         $response = curl_exec($curl);

//         if (curl_errno($curl)) {
//             return [
//                 'status' => false,
//                 'error' => curl_error($curl),
//             ];
//         }

//         curl_close($curl);

//         return json_decode($response, true) ?? [
//             'status' => false,
//             'error' => 'Invalid JSON response',
//         ];
//     }

//     protected function normalizeNumber(string $nomor): string
//     {
//         $nomor = preg_replace('/[^0-9]/', '', $nomor);
//         if (str_starts_with($nomor, '62')) {
//             return '08'.substr($nomor, 2);
//         }
//         if (str_starts_with($nomor, '8')) {
//             return '08'.substr($nomor, 1);
//         }
//         if (str_starts_with($nomor, '08')) {
//             return $nomor;
//         }

//         return $nomor;
//     }
// }

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected array $endpoints;
    protected string $cachePrefix = 'whatsapp_api_';

    public function __construct()
    {
        // Load semua endpoint dari config
        $this->endpoints = [
            'api1' => env('WHATSAPP_API_URL_1'),
            'api2' => env('WHATSAPP_API_URL_2'),
            'api3' => env('WHATSAPP_API_URL_3'),
            'api4' => env('WHATSAPP_API_URL_4'),
            'api5' => env('WHATSAPP_API_URL_5'),
            'api6' => env('WHATSAPP_API_URL_6'),
            'api7' => env('WHATSAPP_API_URL_7'),
            'api8' => env('WHATSAPP_API_URL_8'),
            'api9' => env('WHATSAPP_API_URL_9'),
            'api10' => env('WHATSAPP_API_URL_10'),
        ];

        // Filter hanya endpoint yang ada
        $this->endpoints = array_filter($this->endpoints);
    }

    public function send(string $nomor, string $pesan, ?string $filePath = null): array
    {
        $nomor = $this->normalizeNumber($nomor);
        
        // Coba kirim dengan load balancing
        $result = $this->sendWithLoadBalancing($nomor, $pesan, $filePath);
        
        // Log untuk monitoring
        Log::info('WhatsApp message sent', [
            'nomor' => substr($nomor, 0, 4) . '****', // Partial number for privacy
            'endpoint_used' => $result['endpoint_used'] ?? 'failed',
            'status' => $result['status'],
            'message_length' => strlen($pesan)
        ]);

        return $result;
    }

    protected function sendWithLoadBalancing(string $nomor, string $pesan, ?string $filePath = null): array
    {
        $activeEndpoints = $this->getActiveEndpoints();
        
        if (empty($activeEndpoints)) {
            return [
                'status' => false,
                'error' => 'No active WhatsApp endpoints available',
                'endpoint_used' => null
            ];
        }

        // Pilih endpoint dengan round-robin
        $selectedEndpoint = $this->selectEndpointRoundRobin($activeEndpoints);
        
        // Coba kirim ke endpoint yang dipilih
        $result = $this->sendToEndpoint($selectedEndpoint['key'], $selectedEndpoint['url'], $nomor, $pesan, $filePath);
        
        if ($result['status']) {
            return $result + ['endpoint_used' => $selectedEndpoint['key']];
        }

        // Jika gagal, mark endpoint sebagai down dan coba endpoint lain
        $this->markEndpointAsDown($selectedEndpoint['key']);
        
        // Retry dengan endpoint lain
        return $this->retryWithOtherEndpoints($nomor, $pesan, $filePath, [$selectedEndpoint['key']]);
    }

    protected function retryWithOtherEndpoints(string $nomor, string $pesan, ?string $filePath, array $excludeKeys): array
    {
        $activeEndpoints = $this->getActiveEndpoints();
        
        // Remove excluded endpoints
        $availableEndpoints = array_filter($activeEndpoints, 
            fn($endpoint) => !in_array($endpoint['key'], $excludeKeys)
        );

        if (empty($availableEndpoints)) {
            return [
                'status' => false,
                'error' => 'All WhatsApp endpoints are down',
                'endpoint_used' => null
            ];
        }

        // Coba endpoint pertama yang available
        $endpoint = array_values($availableEndpoints)[0];
        $result = $this->sendToEndpoint($endpoint['key'], $endpoint['url'], $nomor, $pesan, $filePath);
        
        if ($result['status']) {
            return $result + ['endpoint_used' => $endpoint['key']];
        }

        // Mark endpoint down dan retry lagi (recursive)
        $this->markEndpointAsDown($endpoint['key']);
        $excludeKeys[] = $endpoint['key'];
        
        // Batasi retry maksimal 3 endpoint untuk menghindari infinite loop
        if (count($excludeKeys) >= 3) {
            return [
                'status' => false,
                'error' => 'Failed after trying multiple endpoints',
                'endpoint_used' => null
            ];
        }

        return $this->retryWithOtherEndpoints($nomor, $pesan, $filePath, $excludeKeys);
    }

    protected function sendToEndpoint(string $key, string $endpoint, string $nomor, string $pesan, ?string $filePath): array
    {
        $postFields = [
            'message' => $pesan,
            'number' => $nomor,
        ];

        if ($filePath && file_exists($filePath)) {
            $postFields['file_dikirim'] = new \CURLFile(realpath($filePath));
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            // CURLOPT_CONNECTTIMEOUT => 5, // Connection timeout/
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            
            Log::warning("WhatsApp endpoint failed", [
                'endpoint' => $key,
                'curl_error' => $error
            ]);
            
            return [
                'status' => false,
                'error' => $error,
            ];
        }

        curl_close($curl);

        // Check HTTP status code
        if ($httpCode >= 400) {
            Log::warning("WhatsApp endpoint HTTP error", [
                'endpoint' => $key,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            
            return [
                'status' => false,
                'error' => "HTTP {$httpCode}: {$response}",
            ];
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'status' => false,
                'error' => 'Invalid JSON response: ' . $response,
            ];
        }

        // Update endpoint sebagai aktif jika berhasil
        $this->markEndpointAsUp($key);

        return $decodedResponse ?? [
            'status' => false,
            'error' => 'Empty response',
        ];
    }

    protected function getActiveEndpoints(): array
    {
        $activeEndpoints = [];
        
        foreach ($this->endpoints as $key => $url) {
            if (!$this->isEndpointDown($key)) {
                $activeEndpoints[] = ['key' => $key, 'url' => $url];
            }
        }

        return $activeEndpoints;
    }

    protected function selectEndpointRoundRobin(array $activeEndpoints): array
    {
        $cacheKey = $this->cachePrefix . 'round_robin_index';
        $currentIndex = Cache::get($cacheKey, 0);
        
        // Reset index jika melebihi jumlah endpoint aktif
        if ($currentIndex >= count($activeEndpoints)) {
            $currentIndex = 0;
        }
        
        $selectedEndpoint = $activeEndpoints[$currentIndex];
        
        // Update index untuk next request
        $nextIndex = ($currentIndex + 1) % count($activeEndpoints);
        Cache::put($cacheKey, $nextIndex, now()->addHours(24));
        
        return $selectedEndpoint;
    }

    protected function isEndpointDown(string $key): bool
    {
        $cacheKey = $this->cachePrefix . 'down_' . $key;
        return Cache::has($cacheKey);
    }

    protected function markEndpointAsDown(string $key): void
    {
        $cacheKey = $this->cachePrefix . 'down_' . $key;
        // Mark sebagai down untuk 5 menit
        Cache::put($cacheKey, true, now()->addMinutes(5));
        
        Log::warning("WhatsApp endpoint marked as down", [
            'endpoint' => $key,
            'retry_after' => '5 minutes'
        ]);
    }

    protected function markEndpointAsUp(string $key): void
    {
        $cacheKey = $this->cachePrefix . 'down_' . $key;
        Cache::forget($cacheKey);
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
     * Get status semua endpoint untuk monitoring
     */
    public function getEndpointStatus(): array
    {
        $status = [];
        
        foreach ($this->endpoints as $key => $url) {
            $isDown = $this->isEndpointDown($key);
            $status[$key] = [
                'url' => $url,
                'status' => $isDown ? 'down' : 'active',
                'last_checked' => now()->toISOString()
            ];
        }

        return $status;
    }

    /**
     * Force test semua endpoint
     */
    public function testAllEndpoints(): array
    {
        $results = [];
        
        foreach ($this->endpoints as $key => $url) {
            $startTime = microtime(true);
            
            // Test dengan pesan dummy
            $result = $this->sendToEndpoint($key, $url, '0895351856267', 'Test connection', null);
            
            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000); // ms
            
            $results[$key] = [
                'url' => $url,
                'status' => $result['status'] ? 'success' : 'failed',
                'response_time_ms' => $responseTime,
                'error' => $result['error'] ?? null
            ];
        }

        return $results;
    }
}