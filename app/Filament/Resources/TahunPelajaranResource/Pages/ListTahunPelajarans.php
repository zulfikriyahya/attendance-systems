<?php

namespace App\Filament\Resources\TahunPelajaranResource\Pages;

use App\Models\Kelas;
use App\Models\Enrollment;
use Filament\Actions\Action;
use App\Models\TahunPelajaran;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use App\Models\KelasTahunPelajaran;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\TahunPelajaranResource;

class ListTahunPelajarans extends ListRecords
{
    protected static string $resource = TahunPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        // Hanya super admin yang dapat melihat aksi header ini
        if (! Auth::user()->hasRole('super_admin')) {
            return [];
        }
        return [
            CreateAction::make()
                ->label('Tambah Tahun')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),
                // TODO: Enrollment Kelas ke Tahun Pelajaran
            Action::make('enrollment')
                ->label('Enrollment')
                ->outlined()
                ->visible(fn () =>
                    Kelas::exists() &&
                    TahunPelajaran::where('status', true)->exists()
                )
                ->icon('heroicon-o-building-library')
                ->color(Color::Cyan)
                ->requiresConfirmation()
                ->form([
                    Select::make('tahun_pelajaran_id')
                        ->label('Tahun Pelajaran Aktif')
                        ->options(fn () =>
                            TahunPelajaran::where('status', true)
                                ->pluck('nama', 'id')
                        )
                        ->default(fn () =>
                            TahunPelajaran::where('status', true)
                                ->first()?->id
                        )
                        ->required(),

                    Select::make('kelas_id')
                        ->label('Kelas')
                        ->options(fn () => Kelas::pluck('nama', 'id'))
                        ->multiple()
                        ->required(),
                ])
                ->action(function (array $data) {
                $kelasIds = $data['kelas_id'];
                $tahunPelajaranId = $data['tahun_pelajaran_id'];

                $kelasBaru = collect($kelasIds)->filter(function ($kelasId) use ($tahunPelajaranId) {
                    return ! KelasTahunPelajaran::where('kelas_id', $kelasId)
                        ->where('tahun_pelajaran_id', $tahunPelajaranId)
                        ->exists();
                });
                foreach ($kelasBaru as $kelasId) {
                    KelasTahunPelajaran::create([
                        'kelas_id' => $kelasId,
                        'tahun_pelajaran_id' => $tahunPelajaranId,
                    ]);
                }
                if ($kelasBaru->isEmpty()) {
                    Notification::make()
                        ->title('Tidak Ada Kelas Baru')
                        ->body('Semua kelas sudah terdaftar pada tahun pelajaran tersebut.')
                        ->warning()
                        ->send();

                    return;
                }
                Notification::make()
                    ->title('Enrollment Berhasil')
                    ->body("Berhasil mendaftarkan {$kelasBaru->count()} kelas ke tahun pelajaran aktif.")
                    ->success()
                    ->send();
            }),
        ];
    }
}
