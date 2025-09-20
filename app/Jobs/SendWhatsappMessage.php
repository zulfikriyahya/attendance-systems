<?php

// ====================================================
// 1. SINGLE WHATSAPP JOB (Replace all 3 jobs)
// ====================================================

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
            if (!$result['status']) {
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
            'error' => $error
        ]);
    }
}
