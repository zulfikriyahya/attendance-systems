<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Siswa;
use App\Models\PresensiSiswa;
use Illuminate\Bus\Queueable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;

class GenerateLaporanPresensiSiswaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 180 menit timeout

    public $tries = 3; // Retry 3 kali jika gagal

    protected int $bulan;

    protected int $tahun;

    protected int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $bulan, int $tahun, int $userId)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Ambil data presensi
            $presensis = PresensiSiswa::whereMonth('tanggal', $this->bulan)
                ->whereYear('tanggal', $this->tahun)
                ->orderBy('tanggal', 'asc')
                ->get()
                ->groupBy('siswa_id');

            $siswaIdsDenganPresensi = $presensis->keys();

            // Ambil data siswa
            $siswas = Siswa::with(['user', 'jabatan.instansi'])
                ->where(function ($query) use ($siswaIdsDenganPresensi) {
                    $query->where('status', true)
                        ->orWhereIn('id', $siswaIdsDenganPresensi);
                })
                ->get()
                ->sortBy(fn ($siswa) => $siswa->user->name);

            // Generate PDF
            $pdf = Pdf::loadView('exports.presensi-siswa-batch', [
                'siswas' => $siswas,
                'presensis' => $presensis,
                'bulan' => $this->bulan,
                'tahun' => $this->tahun,
            ])->setPaper('A4', 'portrait');

            // Simpan ke storage
            $namaBulan = Carbon::create()->month($this->bulan)->translatedFormat('F');
            $filename = "laporan-presensi-siswa-{$namaBulan}-{$this->tahun}-".time().'.pdf';
            $path = "laporan-presensi/{$filename}";

            Storage::disk('public')->put($path, $pdf->output());

            // Kirim notifikasi sukses ke user
            $user = User::find($this->userId);

            FilamentNotification::make()
                ->title('Laporan Berhasil Dibuat')
                ->body("✅ Laporan presensi siswa untuk bulan {$namaBulan} {$this->tahun} telah selesai dibuat.")
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->actions([
                    NotificationAction::make('download')
                        ->label('Download Laporan')
                        ->url(Storage::disk('public')->url($path))
                        ->openUrlInNewTab()
                        ->button()
                        ->color('success'),
                ])
                ->sendToDatabase($user);

            // Kirim notifikasi real-time jika user online
            if ($user) {
                $user->notify(new \App\Notifications\LaporanSelesaiNotification([
                    'title' => 'Laporan Berhasil Dibuat',
                    'message' => "Laporan presensi siswa untuk bulan {$namaBulan} {$this->tahun} telah selesai dibuat.",
                    'download_url' => Storage::disk('public')->url($path),
                ]));
            }

            // Hapus file lama (opsional - file lebih dari 7 hari)
            $this->cleanOldReports();

        } catch (\Exception $e) {
            Log::error('Error generating laporan presensi: '.$e->getMessage());

            // Kirim notifikasi error
            $user = User::find($this->userId);
            $namaBulan = Carbon::create()->month($this->bulan)->translatedFormat('F');

            FilamentNotification::make()
                ->title('Gagal Membuat Laporan')
                ->body("❌ Terjadi kesalahan saat membuat laporan presensi siswa untuk bulan {$namaBulan} {$this->tahun}. Silakan coba lagi.")
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->sendToDatabase($user);

            throw $e; // Re-throw untuk retry mechanism
        }
    }

    /**
     * Hapus laporan lama (lebih dari 7 hari)
     */
    protected function cleanOldReports(): void
    {
        $files = Storage::disk('public')->files('laporan-presensi');

        foreach ($files as $file) {
            if (Storage::disk('public')->lastModified($file) < now()->subDays(7)->timestamp) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $user = User::find($this->userId);
        $namaBulan = Carbon::create()->month($this->bulan)->translatedFormat('F');

        FilamentNotification::make()
            ->title('Laporan Gagal Dibuat')
            ->body("❌ Laporan presensi siswa untuk bulan {$namaBulan} {$this->tahun} gagal dibuat setelah beberapa percobaan. Silakan hubungi administrator.")
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->persistent()
            ->sendToDatabase($user);
    }
}
