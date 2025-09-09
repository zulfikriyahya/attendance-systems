<?php

namespace App\Filament\Resources\SiswaResource\Pages;

use App\Filament\Resources\SiswaResource;
use App\Models\Siswa;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class CetakKartuSiswa extends Page
{
    protected static string $resource = SiswaResource::class;

    protected static string $view = 'filament.resources.siswa-resource.pages.cetak-kartu-siswa';

    public ?Siswa $siswa = null;

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
        if ($record instanceof Siswa) {
            $this->siswa = $record;
        } else {
            // kemungkinan $record adalah id (string/int) atau array berisi id
            $id = null;
            if (is_array($record) && isset($record['id'])) {
                $id = $record['id'];
            } elseif (is_scalar($record) && $record !== null) {
                $id = $record;
            }

            if ($id !== null) {
                $this->siswa = Siswa::find($id);
            }
        }

        if (! $this->siswa) {
            $this->kartuUrl = null;

            return;
        }

        // Ambil nama dasar dari avatar (tanpa path & ekstensi)
        $avatarPath = $this->siswa->user->avatar ?? $this->siswa->avatar ?? null;
        $baseName = $avatarPath ? pathinfo($avatarPath, PATHINFO_FILENAME) : null;

        if (! $baseName) {
            $this->kartuUrl = null;

            return;
        }

        // Coba cek dengan beberapa ekstensi umum
        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            $path = "siswa/kartu/{$baseName}.{$ext}";
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
