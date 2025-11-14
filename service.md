```php
// config/whatsapp.php
return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Service Configuration
    |--------------------------------------------------------------------------
    */

    'endpoint' => env('WHATSAPP_ENDPOINT', 'https://api.whatsapp.local/send'),

    'timeout' => env('WHATSAPP_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */

    'rate_limits' => [
        'presensi' => [
            'messages_per_minute' => env('WHATSAPP_PRESENSI_RATE', 35),
            'max_delay_minutes' => env('WHATSAPP_PRESENSI_MAX_DELAY', 30),
            'priority_statuses' => ['Terlambat', 'Pulang Cepat'],
        ],

        'bulk' => [
            'messages_per_minute' => env('WHATSAPP_BULK_RATE', 20),
            'max_delay_hours' => env('WHATSAPP_BULK_MAX_DELAY', 2),
            'types' => [
                'alfa' => ['priority' => 1, 'extra_delay' => 0],
                'mangkir' => ['priority' => 2, 'extra_delay' => [60, 180]],
                'bolos' => ['priority' => 2, 'extra_delay' => [60, 180]],
            ],
        ],

        'informasi' => [
            'messages_per_minute' => env('WHATSAPP_INFORMASI_RATE', 25),
            'max_delay_minutes' => env('WHATSAPP_INFORMASI_MAX_DELAY', 60),
            'extra_delay' => [30, 90],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'default_queue' => env('WHATSAPP_QUEUE', 'default'),
        'retry_attempts' => env('WHATSAPP_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WHATSAPP_RETRY_DELAY', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('WHATSAPP_MONITORING_ENABLED', true),

        'thresholds' => [
            'queue' => [
                'warning' => env('WHATSAPP_QUEUE_WARNING', 5000),
                'critical' => env('WHATSAPP_QUEUE_CRITICAL', 10000),
            ],

            'failed_jobs' => [
                'warning' => env('WHATSAPP_FAILED_WARNING', 20),
                'critical' => env('WHATSAPP_FAILED_CRITICAL', 100),
            ],

            'hourly_messages' => [
                'warning' => env('WHATSAPP_HOURLY_WARNING', 2000),
                'critical' => env('WHATSAPP_HOURLY_CRITICAL', 3000),
            ],
        ],

        'cache_ttl' => env('WHATSAPP_CACHE_TTL', 3600), // 1 hour

        'alerts' => [
            'enabled' => env('WHATSAPP_ALERTS_ENABLED', false),
            'webhook_url' => env('WHATSAPP_ALERT_WEBHOOK'),
            'email_recipients' => env('WHATSAPP_ALERT_EMAILS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'presensi' => [
            'header' => 'PTSP {instansi}',
            'footer' => '© 2022 - {tahun} {instansi}',

            'masuk' => [
                'greeting' => [
                    'siswa' => 'Selamat mengikuti kegiatan pembelajaran hari ini.',
                    'pegawai' => 'Selamat menjalankan tugas dan tanggung jawab Anda.',
                ],
                'bulk_message' => [
                    'siswa' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.',
                    'pegawai' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi masuk.',
                ],
            ],

            'pulang' => [
                'greeting' => [
                    'siswa' => 'Terima kasih telah mengikuti kegiatan pembelajaran hari ini.',
                    'pegawai' => 'Terima kasih atas dedikasi dan kinerja Anda hari ini.',
                ],
                'bulk_message' => [
                    'siswa' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.',
                    'pegawai' => 'Status presensi ini tercatat secara otomatis karena tidak terdeteksi melakukan presensi pulang.',
                ],
            ],
        ],

        'informasi' => [
            'header' => 'PTSP {instansi}',
            'footer' => '© 2022 - {tahun} {instansi}',
            'max_content_length' => env('WHATSAPP_INFO_MAX_LENGTH', 200),

            'greetings' => [
                'siswa' => 'Kepada Bapak/Ibu/Wali Siswa yang terhormat,',
                'pegawai' => 'Kepada Bapak/Ibu yang terhormat,',
            ],

            'closing' => [
                'siswa' => 'Terima kasih atas perhatiannya. Tetap semangat belajar!',
                'pegawai' => 'Terima kasih atas perhatian dan kerjasamanya.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Number Configuration
    |--------------------------------------------------------------------------
    */

    'phone' => [
        'validation' => [
            'enabled' => env('WHATSAPP_PHONE_VALIDATION', true),
            'pattern' => '/^08[0-9]{8,12}$/',
            'country_code' => '62',
        ],

        'normalization' => [
            'remove_country_code' => true,
            'prefix' => '08',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Attachment Configuration
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'enabled' => env('WHATSAPP_ATTACHMENTS_ENABLED', true),
        'max_size' => env('WHATSAPP_MAX_FILE_SIZE', 16777216), // 16MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'storage_path' => 'app/public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', true),
        'level' => env('WHATSAPP_LOG_LEVEL', 'info'),
        'channels' => [
            'success' => env('WHATSAPP_SUCCESS_LOG', 'single'),
            'error' => env('WHATSAPP_ERROR_LOG', 'single'),
        ],
        'context' => [
            'include_payload' => env('WHATSAPP_LOG_PAYLOAD', false),
            'include_response' => env('WHATSAPP_LOG_RESPONSE', true),
        ],
    ],
];
```

---

```php
// Jobs/BroadcastInformasi.php

namespace App\Jobs;

use App\Models\Informasi;
use App\Models\Pegawai;
use App\Models\Siswa;
use App\Services\WhatsappDelayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastInformasi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Informasi $informasi
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsappDelayService $delayService): void
    {
        $notifCounter = 0;

        // Ambil semua siswa dengan nomor telepon yang valid
        $siswa = Siswa::with('jabatan.instansi', 'user')
            ->where('jabatan_id', $this->informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        // Ambil semua pegawai dengan nomor telepon yang valid
        $pegawai = Pegawai::with('jabatan.instansi', 'user')
            ->where('jabatan_id', $this->informasi->jabatan_id)
            ->whereNotNull('telepon')
            ->where('telepon', '!=', '')
            ->where('status', true)
            ->get();

        $totalRecipients = $siswa->count() + $pegawai->count();

        // Jika tidak ada penerima, return
        if ($totalRecipients === 0) {
            logger()->warning('No recipients found for informasi broadcast', [
                'informasi_id' => $this->informasi->id,
                'judul' => $this->informasi->judul,
            ]);

            return;
        }

        // Proses pengiriman ke siswa
        foreach ($siswa as $student) {
            $nama = $student->user?->name ?? $student->nama ?? 'Siswa';
            $instansi = $student->jabatan?->instansi?->nama ?? 'Instansi';

            // Hitung delay berdasarkan counter untuk menghindari rate limit
            $delay = $delayService->calculateBulkDelay($notifCounter, 'informasi');

            SendWhatsappMessage::dispatch(
                $student->telepon,
                'informasi',
                [
                    'judul' => $this->informasi->judul,
                    'isi' => $this->informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $this->informasi->lampiran,
                    'isSiswa' => true,
                ]
            )
                ->delay($delay)
                ->onQueue('whatsapp'); // Gunakan queue khusus untuk WhatsApp

            $notifCounter++;

            // Log setiap 50 pesan untuk monitoring
            if ($notifCounter % 50 === 0) {
                logger()->info("Broadcast progress: {$notifCounter} messages queued");
            }
        }

        // Proses pengiriman ke pegawai
        foreach ($pegawai as $employee) {
            $nama = $employee->user?->name ?? $employee->nama ?? 'Pegawai';
            $instansi = $employee->jabatan?->instansi?->nama ?? 'Instansi';

            // Hitung delay berdasarkan counter untuk menghindari rate limit
            $delay = $delayService->calculateBulkDelay($notifCounter, 'informasi');

            SendWhatsappMessage::dispatch(
                $employee->telepon,
                'informasi',
                [
                    'judul' => $this->informasi->judul,
                    'isi' => $this->informasi->isi,
                    'nama' => $nama,
                    'instansi' => $instansi,
                    'lampiran' => $this->informasi->lampiran,
                    'isSiswa' => false,
                ]
            )
                ->delay($delay)
                ->onQueue('whatsapp'); // Gunakan queue khusus untuk WhatsApp

            $notifCounter++;

            // Log setiap 50 pesan untuk monitoring
            if ($notifCounter % 50 === 0) {
                logger()->info("Broadcast progress: {$notifCounter} messages queued");
            }
        }

        // Log broadcast dengan estimasi waktu selesai
        $maxDelayMinutes = $delayService->calculateBulkDelay($notifCounter - 1, 'informasi')->diffInMinutes(now());

        logger()->info('Informasi WhatsApp broadcast dispatched', [
            'informasi_id' => $this->informasi->id,
            'judul' => $this->informasi->judul,
            'total_recipients' => $totalRecipients,
            'siswa' => $siswa->count(),
            'pegawai' => $pegawai->count(),
            'max_delay_minutes' => $maxDelayMinutes,
            'estimated_completion' => now()->addMinutes($maxDelayMinutes)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        logger()->error('Failed to broadcast informasi', [
            'informasi_id' => $this->informasi->id,
            'judul' => $this->informasi->judul,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

---

```php
// Jobs/SendWhatsappMessage.php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Services\WhatsappService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWhatsappMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $nomor;

    public string $type; // 'presensi', 'presensi_bulk', 'informasi'

    public array $data;

    public function __construct(string $nomor, string $type, array $data)
    {
        $this->nomor = $nomor;
        $this->type = $type;
        $this->data = $data;
    }

    public function handle(WhatsappService $whatsapp): void
    {
        try {
            switch ($this->type) {
                case 'presensi':
                    $result = $whatsapp->sendPresensi(
                        $this->nomor,
                        $this->data['jenis'],
                        $this->data['status'],
                        $this->data['waktu'],
                        $this->data['nama'],
                        $this->data['isSiswa'],
                        $this->data['instansi'],
                        false // bulk = false
                    );
                    break;

                case 'presensi_bulk':
                    $result = $whatsapp->sendPresensi(
                        $this->nomor,
                        $this->data['jenis'],
                        $this->data['status'],
                        'Tidak terdeteksi melakukan presensi. (_Apabila kartu Anda hilang atau mengalami kerusakan, mohon segera menghubungi kami untuk mendapatkan bantuan lebih lanjut._)',
                        $this->data['waktu'],
                        $this->data['nama'],
                        $this->data['isSiswa'],
                        $this->data['instansi'],
                        true // bulk = true
                    );
                    break;

                case 'informasi':
                    $result = $whatsapp->sendInformasi(
                        $this->nomor,
                        $this->data['judul'],
                        $this->data['isi'],
                        $this->data['nama'],
                        $this->data['instansi'],
                        $this->data['lampiran'] ?? null,
                        $this->data['isSiswa']
                    );
                    break;

                default:
                    throw new \InvalidArgumentException("Unknown message type: {$this->type}");
            }

            // Log jika gagal
            if (! $result['status']) {
                $this->logError($result['error'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
            throw $e; // Re-throw untuk queue retry mechanism
        }
    }

    private function logError(string $error): void
    {
        logger()->error('WhatsApp message failed', [
            'nomor' => $this->nomor,
            'type' => $this->type,
            'data' => $this->data,
            'error' => $error,
        ]);
    }
}
```

---

```php
// Pengajuan Kartu Resource

// Kirim ke WhatsApp
$phoneNumber = null;
$userName = $record->user->name;

// Cek apakah user adalah siswa atau pegawai
if ($record->user->siswa) {
    $phoneNumber = $record->user->siswa->telepon;
} elseif ($record->user->pegawai) {
    $phoneNumber = $record->user->pegawai->telepon;
}

if ($phoneNumber) {
    $whatsappService = new WhatsappService;
    $tahunIni = date('Y');
    $namaInstansi = Instansi::all()->first()->nama;
    $instansi = strtoupper($namaInstansi);
    $url = config('app.url').'/admin/pengajuan-kartu/'.$record->id;
    $message = <<<TEXT
    *PTSP {$instansi}*

    ———————————————————
    🪪 *Kartu Presensi Sedang Diproses*
    ———————————————————
    Halo {$userName},
    Pengajuan kartu Anda dengan nomor *{$record->nomorPengajuanKartu}* sedang diproses.
    Mohon menunggu kabar selanjutnya.

    Terima kasih! 🙏
    ———————————————————
    Tautan : {$url}
    ———————————————————

    *© 2022 - {$tahunIni} {$instansi}*
    TEXT;
    $whatsappService->send($phoneNumber, $message);
}
```

---

```php
// Services/PresensiService.php
namespace App\Services;

use App\Enums\StatusPresensi;
use App\Enums\StatusPulang;
use App\Jobs\SendWhatsappMessage;
use App\Models\JadwalPresensi;
use App\Models\Pegawai;
use App\Models\PresensiPegawai;
use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PresensiService
{
    protected WhatsappDelayService $delayService;

    public function __construct(WhatsappDelayService $delayService)
    {
        $this->delayService = $delayService;
    }

    public function prosesPresensi(string $rfid, ?string $timestamp = null, bool $isSync = false, ?string $deviceId = null): array
    {
        $now = $timestamp ? Carbon::parse($timestamp) : now();
        $today = $now->toDateString();
        $nowTime = $now->format('H:i:s');
        $hariIni = $now->isoFormat('dddd');

        return DB::transaction(function () use ($rfid, $now, $today, $nowTime, $hariIni, $isSync) {
            foreach ([Pegawai::class => false, Siswa::class => true] as $model => $isSiswa) {

                // Cari user dengan RFID
                $user = $model::with('jabatan.jadwalPresensis', 'jabatan.instansi')
                    ->where('rfid', $rfid)
                    ->first();

                if (! $user) {
                    continue;
                }

                // Ambil jadwal hari ini dari cache
                $jadwalHariIni = Cache::remember(
                    "jadwal_presensi:{$hariIni}",
                    now()->addMinutes(10),
                    fn () => JadwalPresensi::where('status', true)
                        ->where('hari', $hariIni)
                        ->with('jabatans:id')
                        ->get()
                        ->flatMap(
                            fn ($jadwal) => $jadwal->jabatans->mapWithKeys(
                                fn ($jabatan) => [(string) $jabatan->id => collect([$jadwal])]
                            )
                        )
                );

                $jadwal = optional($jadwalHariIni->get((string) $user->jabatan_id))->first();

                if (! $jadwal) {
                    return [
                        'status' => 'error',
                        'message' => 'Tidak ada jadwal presensi untuk hari ini',
                    ];
                }

                $presensiModel = $isSiswa ? PresensiSiswa::class : PresensiPegawai::class;
                $field = $isSiswa ? 'siswa_id' : 'pegawai_id';
                $presensi = $presensiModel::where($field, $user->id)
                    ->whereDate('tanggal', $today)
                    ->first();

                $nama = $user->user?->name ?? $user->nama ?? 'Tidak dikenal';
                $instansi = $user->jabatan?->instansi?->nama ?? 'Instansi';

                // Presensi Masuk
                if (! $presensi) {
                    $status = $nowTime <= $jadwal->jamDatang
                        ? StatusPresensi::Hadir
                        : StatusPresensi::Terlambat;

                    $presensiModel::create([
                        $field => $user->id,
                        'tanggal' => $today,
                        'jamDatang' => $nowTime,
                        'statusPresensi' => $status,
                        'is_synced' => $isSync,
                        'synced_at' => $isSync ? now() : null,
                    ]);

                    $this->sendNotif($user->telepon, 'Presensi Masuk', $status->label(), $nowTime, $nama, $isSiswa, $instansi, $isSync);

                    return [
                        'status' => 'success',
                        'message' => "Presensi masuk berhasil sebagai {$status->label()}",
                        'data' => compact('nama', 'nowTime', 'isSync') + ['status' => $status->value],
                    ];
                }

                // Presensi Pulang
                if ($presensi->jamPulang) {
                    return [
                        'status' => 'error',
                        'message' => 'Anda sudah presensi masuk dan pulang hari ini',
                    ];
                }

                if (! $isSync && $now->lt(Carbon::createFromTimeString($presensi->jamDatang)->addMinutes(30))) {
                    return [
                        'status' => 'error',
                        'message' => 'Presensi kedua hanya diizinkan setelah 30 menit',
                    ];
                }

                $statusPulang = $nowTime <= $jadwal->jamPulang
                    ? StatusPulang::PulangCepat
                    : StatusPulang::Pulang;

                $presensi->update([
                    'jamPulang' => $nowTime,
                    'statusPulang' => $statusPulang,
                    'is_synced' => $isSync,
                    'synced_at' => $isSync ? now() : null,
                ]);

                $this->sendNotif($user->telepon, 'Presensi Pulang', $statusPulang->label(), $nowTime, $nama, $isSiswa, $instansi, $isSync);

                return [
                    'status' => 'success',
                    'message' => 'Presensi pulang berhasil',
                    'data' => compact('nama', 'nowTime', 'isSync') + ['status' => $statusPulang->value],
                ];
            }

            return ['status' => 'error', 'message' => 'RFID tidak dikenal'];
        });
    }

    private function sendNotif(
        string $telepon,
        string $jenis,
        string $status,
        string $jam,
        string $nama,
        bool $isSiswa,
        string $instansi,
        bool $isSync
    ): void {
        if (! $isSync) {
            $delay = $this->delayService->calculateRealtimeDelay($status);

            // Dispatch unified job
            SendWhatsappMessage::dispatch(
                $telepon,
                'presensi', // type
                [
                    'jenis' => $jenis,
                    'status' => $status,
                    'waktu' => $jam,
                    'nama' => $nama,
                    'isSiswa' => $isSiswa,
                    'instansi' => $instansi,
                ]
            )->delay($delay);
        }
    }
}
```

---

```php
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
```

---

```php
// Services/WhatsappService.php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

        // Rate limiting check with auto delay (per type)
        if ($this->isRateLimited($type)) {
            return $this->buildErrorResponse('Rate limit exceeded', $nomor);
        }

        // Apply automatic delay to prevent burst traffic
        $this->applyRateLimit($type);

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

            // Increment rate limit counter after successful send
            $this->incrementRateLimitCounter($type);

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

        $header = strtoupper(str_replace('{instansi}', $instansi, $templates['header'] ?? 'PTSP {instansi}'));
        $footer = strtoupper(str_replace(['{tahun}', '{instansi}'], [$tahunIni, $instansi], $templates['footer'] ?? '© 2022 - {tahun} {instansi}'));

        $pesan = <<<TEXT
        *{$header}*

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

        *{$footer}*
        TEXT;

        // Determine type: presensi or bulk
        $type = $isBulk ? 'bulk' : 'presensi';
        $result = $this->send($nomor, $pesan, null, $type);

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
        $urlInformasi = config('app.url').'/admin/informasi';

        // Configurable content length
        $maxLength = $templates['max_content_length'] ?? 200;
        $isiSingkat = strlen($isi) > $maxLength
            ? substr($isi, 0, $maxLength).'... (Baca selengkapnya.)'
            : $isi;

        // Get template greetings and closings
        $userType = $isSiswa ? 'siswa' : 'pegawai';
        $title = strtoupper($judul);
        $greeting = $templates['greetings'][$userType]
            ?? ($isSiswa ? 'Kepada Bapak/Ibu/Wali Siswa yang terhormat,' : 'Kepada Bapak/Ibu yang terhormat,');

        $closing = $templates['closing'][$userType]
            ?? ($isSiswa ? 'Terima kasih atas perhatiannya. Tetap semangat belajar!' : 'Terima kasih atas perhatian dan kerjasamanya.');

        $header = strtoupper(str_replace('{instansi}', $instansi, $templates['header'] ?? 'PTSP {instansi}'));
        $footer = strtoupper(str_replace(['{tahun}', '{instansi}'], [$tahunIni, $instansi], $templates['footer'] ?? '© 2022 - {tahun} {instansi}'));

        $pesan = <<<TEXT
        *{$header}*

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

        *{$footer}*
        TEXT;

        // Send main message with 'informasi' type
        $result = $this->send($nomor, $pesan, null, 'informasi');

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
     * Apply automatic delay between messages to prevent burst traffic
     * Delay based on message type configuration with random variation
     */
    protected function applyRateLimit(string $type = 'presensi'): void
    {
        // Get rate per minute from config based on type
        $ratePerMinute = $this->config['rate_limits'][$type]['messages_per_minute'] ?? 20;

        // Calculate base delay in milliseconds: 60000ms / rate = delay per message
        $baseDelayMs = (int) (60000 / $ratePerMinute);

        // Add random variation ±20% to make it more natural
        $variation = $baseDelayMs * 0.2; // 20% variation
        $minDelay = (int) ($baseDelayMs - $variation);
        $maxDelay = (int) ($baseDelayMs + $variation);

        // Random delay between min and max
        $randomDelayMs = random_int($minDelay, $maxDelay);

        usleep($randomDelayMs * 1000); // Convert to microseconds
    }

    /**
     * Check if service is rate limited (per minute basis, per type)
     */
    protected function isRateLimited(string $type = 'presensi'): bool
    {
        $keyMinute = "whatsapp_rate_limit_{$type}_".date('Y-m-d-H-i');
        $limit = $this->config['rate_limits'][$type]['messages_per_minute'] ?? 20;

        $current = Cache::get($keyMinute, 0);

        return $current >= $limit;
    }

    /**
     * Increment rate limit counter per type
     */
    protected function incrementRateLimitCounter(string $type = 'presensi'): void
    {
        $keyMinute = "whatsapp_rate_limit_{$type}_".date('Y-m-d-H-i');
        Cache::increment($keyMinute, 1);

        // Set expiry 2 menit untuk cleanup otomatis
        if (Cache::get($keyMinute) === 1) {
            Cache::put($keyMinute, 1, now()->addMinutes(2));
        }
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
     * Track presensi-specific metrics
     */
    protected function trackPresensiMetrics(string $jenis, string $status, bool $isBulk, bool $success): void
    {
        $key = 'whatsapp_presensi_stats_'.date('Y-m-d');
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
        $key = 'whatsapp_informasi_stats_'.date('Y-m-d');
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
        $successKey = 'whatsapp_metrics_success_'.date('Y-m-d-H');
        $errorKey = 'whatsapp_metrics_error_'.date('Y-m-d-H');

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
            'is_rate_limited' => $this->isRateLimited('presensi'),
        ];
    }
}
```

---

```php
// AppServiceProvider.php
<?php

namespace App\Providers;

use App\Services\WhatsappDelayService;
use App\Services\WhatsappService;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsappDelayService::class);
        $this->app->singleton(WhatsappService::class, function ($app) {
            return new WhatsappService;
        });
    }

    public function boot(): void
    {
        Model::unguard();

        setlocale(LC_TIME, 'id_ID.utf8');
        Carbon::setLocale('id');

        FilamentColor::register([
            'primary' => Color::hex('#0f766e'),
            'gray' => Color::hex('#1e293b'),
            'info' => Color::hex('#6366f1'),
            'success' => Color::hex('#10b981'),
            'warning' => Color::hex('#f59e0b'),
            'danger' => Color::hex('#ef4444'),
        ]);

        RateLimiter::for('device-stats', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('bulk-sync', function (Request $request) {
            return Limit::perHour(20)->by($request->ip());
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MonitorWhatsappQueue::class,
                \App\Console\Commands\WhatsappMaintenance::class,
                // Add other commands here
            ]);
        }
    }
}

```

---

```env
# APLIKASI
APP_NAME="MTs Negeri 1 Pandeglang"
APP_ENV=production
APP_KEY=base64:sJj3BbDlVpTg8JUJavtOt0Lr6pI3lAyIFosYDzPmB48=
APP_DEBUG=false
APP_URL=

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

TIMEZONE="Asia/Jakarta"

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4
BCRYPT_ROUNDS=12

# LOGGING
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# DATABASE
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mtsn1pandeglang
DB_USERNAME=root
DB_PASSWORD=18012000

# SESSION
SESSION_DRIVER=redis
SESSION_LIFETIME=525600             # dalam menit (525600 menit = 1 tahun)
SESSION_EXPIRE_ON_CLOSE=true        # apakah sesi berakhir jika browser ditutup
SESSION_ENCRYPT=false               # enkripsi data sesi
SESSION_PATH=/                      # path cookie sesi
SESSION_DOMAIN=null                 # domain cookie sesi

# FILES & QUEUE
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis
CACHE_PREFIX=presensi-mtsn1pandeglang_

# REDIS (opsional, untuk session/queue/cache yang lebih cepat)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0                    # Database Redis terpisah
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
REDIS_PREFIX=presensi-mtsn1pandeglang_

# EMAIL
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# AWS (opsional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# FRONTEND
VITE_APP_NAME="${APP_NAME}"
API_SECRET=P@ndegl@ng_14012000*

# KONFIGURASI APLIKASI
BIAYA_KARTU=15000

# WHATSAPP SERVICE
WHATSAPP_ENDPOINT=https://example.com/send-message
WHATSAPP_TIMEOUT=15

# RATE LIMITING
WHATSAPP_PRESENSI_RATE=35           # batas pesan presensi / menit
WHATSAPP_PRESENSI_MAX_DELAY=30      # delay maksimal (detik)
WHATSAPP_BULK_RATE=20               # batas pesan bulk / menit
WHATSAPP_BULK_MAX_DELAY=2           # delay maksimal (detik)
WHATSAPP_INFORMASI_RATE=25          # batas pesan informasi / menit
WHATSAPP_INFORMASI_MAX_DELAY=60     # delay maksimal (detik)

# QUEUE
WHATSAPP_QUEUE=default              # nama antrian WhatsApp
WHATSAPP_RETRY_ATTEMPTS=3           # jumlah percobaan ulang
WHATSAPP_RETRY_DELAY=60             # jeda antar retry (detik)

# MONITORING
WHATSAPP_MONITORING_ENABLED=true
WHATSAPP_QUEUE_WARNING=5000         # warning jika antrian >= 5000
WHATSAPP_QUEUE_CRITICAL=10000       # critical jika antrian >= 10000
WHATSAPP_FAILED_WARNING=20          # warning jika gagal >= 20
WHATSAPP_FAILED_CRITICAL=100        # critical jika gagal >= 100
WHATSAPP_HOURLY_WARNING=2000        # warning jika pesan/jam >= 2000
WHATSAPP_HOURLY_CRITICAL=3000       # critical jika pesan/jam >= 3000
WHATSAPP_CACHE_TTL=3600             # waktu simpan cache (detik)

# ALERTS (opsional)
WHATSAPP_ALERTS_ENABLED=false       # aktifkan alert/notifikasi
WHATSAPP_ALERT_WEBHOOK=             # webhook alert
WHATSAPP_ALERT_EMAILS=              # email tujuan alert

# PESAN
WHATSAPP_INFO_MAX_LENGTH=500        # panjang maksimal pesan

# VALIDASI NOMOR TELEPON
WHATSAPP_PHONE_VALIDATION=true

# LAMPIRAN FILE
WHATSAPP_ATTACHMENTS_ENABLED=true   # aktifkan lampiran file
WHATSAPP_MAX_FILE_SIZE=16777216     # ukuran maksimal file (byte) → 16MB

# LOGGING
WHATSAPP_LOGGING_ENABLED=true
WHATSAPP_LOG_LEVEL=info
WHATSAPP_SUCCESS_LOG=single
WHATSAPP_ERROR_LOG=single
WHATSAPP_LOG_PAYLOAD=false          # log isi request
WHATSAPP_LOG_RESPONSE=true          # log response
```
