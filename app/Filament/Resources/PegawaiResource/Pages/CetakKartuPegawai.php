<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Models\Pegawai;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class CetakKartuPegawai extends Page
{
    protected static string $resource = PegawaiResource::class;

    protected static string $view = 'filament.resources.pegawai-resource.pages.cetak-kartu-pegawai';

    public ?Pegawai $pegawai = null;

    public ?string $kartuUrl = null;

    /**
     * Mount menerima $record tanpa type-hint karena Filament bisa mengirim string (id) atau model.
     *
     * @param  mixed  $record
     */
    public function mount($record = null): void
    {
        static::authorizeResourceAccess();

        // Jika Filament memberikan model langsung
        if ($record instanceof Pegawai) {
            $this->pegawai = $record;
        } else {
            // kemungkinan $record adalah id (string/int) atau array berisi id
            $id = null;
            if (is_array($record) && isset($record['id'])) {
                $id = $record['id'];
            } elseif (is_scalar($record) && $record !== null) {
                $id = $record;
            }

            if ($id !== null) {
                $this->pegawai = Pegawai::find($id);
            }
        }

        if (! $this->pegawai) {
            $this->kartuUrl = null;

            return;
        }

        // Ambil nama dasar dari avatar (tanpa path & ekstensi)
        $avatarPath = $this->pegawai->user->avatar ?? $this->pegawai->avatar ?? null;
        $baseName = $avatarPath ? pathinfo($avatarPath, PATHINFO_FILENAME) : null;

        if (! $baseName) {
            $this->kartuUrl = null;

            return;
        }

        // Coba cek dengan beberapa ekstensi umum
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $path = "pegawai/kartu/{$baseName}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                $this->kartuUrl = asset("storage/{$path}");

                return;
            }
        }

        // Kalau semua gagal
        $this->kartuUrl = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak')
                ->label('Unduh File')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('unduhFile'),
        ];
    }

    public function unduhFile()
    {
        // Implementasi PDF (opsional)
    }
}
