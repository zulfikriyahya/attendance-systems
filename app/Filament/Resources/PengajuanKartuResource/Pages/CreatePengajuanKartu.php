<?php

namespace App\Filament\Resources\PengajuanKartuResource\Pages;

use App\Models\User;
use App\Models\PengajuanKartu;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PengajuanKartuResource;

class CreatePengajuanKartu extends CreateRecord
{
    protected static string $resource = PengajuanKartuResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate nomor pengajuan yang unik sebelum create
        $userId = $data['user_id'] ?? Auth::id();
        $data['nomorPengajuanKartu'] = $this->generateNomorPengajuan($userId);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Kirim notifikasi sukses
        Notification::make()
            ->title('Pengajuan Berhasil')
            ->body('Pengajuan kartu baru telah berhasil disubmit dengan nomor: ' . $this->record->nomorPengajuanKartu)
            ->success()
            ->duration(5000)
            ->send();
        
        // Kirim notifikasi ke admin
        $this->sendNotificationToAdmins($this->record, $this->record->alasanPengajuanKartu);
    }

    private function generateNomorPengajuan(int $userId): string
    {
        $today = now()->format('Ymd');
        $userIdPadded = str_pad($userId, 4, '0', STR_PAD_LEFT);

        // Cari nomor urut terakhir untuk hari ini
        $lastNumber = PengajuanKartu::where('nomorPengajuanKartu', 'LIKE', "PK-{$today}-%")
            ->orderBy('nomorPengajuanKartu', 'desc')
            ->first();

        $sequence = 1;
        if ($lastNumber) {
            $parts = explode('-', $lastNumber->nomorPengajuanKartu);
            if (count($parts) >= 4) {
                $sequence = (int) $parts[3] + 1;
            }
        }

        $sequencePadded = str_pad($sequence, 3, '0', STR_PAD_LEFT);

        return "PK-{$today}-{$userIdPadded}-{$sequencePadded}";
    }

    private function sendNotificationToAdmins($pengajuanKartu, string $alasan): void
    {
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'wali_kelas']);
        })->get();

        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('Pengajuan Kartu Baru')
                ->body('User ' . Auth::user()->name . ' mengajukan kartu baru dengan alasan: ' . $alasan)
                ->info()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('lihat')
                        ->label('Lihat Detail')
                        ->url(PengajuanKartuResource::getUrl('view', ['record' => $pengajuanKartu]))
                        ->button(),
                ])
                ->sendToDatabase($admin);
        }
    }
}