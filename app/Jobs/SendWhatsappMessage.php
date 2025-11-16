<?php

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

    public string $type; // 'presensi', 'presensi_bulk', 'informasi', 'pengajuan_kartu'

    public array $data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;

    /**
     * The number of seconds to wait before retrying the job.
     * Menggunakan exponential backoff agar tidak mengganggu antrian normal
     *
     * @var array
     */
    public $backoff;

    public function __construct(string $nomor, string $type, array $data)
    {
        $this->nomor = $nomor;
        $this->type = $type;
        $this->data = $data;

        // Ambil retry config dari whatsapp config
        $this->tries = config('whatsapp.queue.retry_attempts', 3);

        // Exponential backoff: makin lama makin panjang delay-nya
        // Retry 1: 2 menit, Retry 2: 5 menit, Retry 3: 10 menit
        $this->backoff = [120, 300, 600];
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

                case 'pengajuan_kartu':
                    $result = $whatsapp->sendPengajuanKartu(
                        $this->nomor,
                        $this->data['nama'],
                        $this->data['nomor_pengajuan'],
                        $this->data['instansi'],
                        $this->data['pengajuan_id'],
                        $this->data['notification_type'], // 'proses' atau 'selesai'
                        $this->data['biaya'] ?? null
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
}
