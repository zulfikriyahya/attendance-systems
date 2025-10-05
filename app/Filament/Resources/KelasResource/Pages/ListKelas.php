<?php

namespace App\Filament\Resources\KelasResource\Pages;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Pegawai;
use App\Models\Enrollment;
use App\Models\KelasSiswa;
use Filament\Actions\Action;
use App\Models\TahunPelajaran;
use Filament\Actions\CreateAction;
use Filament\Support\Colors\Color;
use App\Models\KelasTahunPelajaran;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Filament\Resources\KelasResource;
use Filament\Resources\Pages\ListRecords;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
    {
        // Hanya super admin yang dapat melihat aksi header ini
        if (! Auth::user()->hasRole('super_admin')) {
            return [];
        }

        return [
            CreateAction::make()
                ->label('Tambah Kelas')
                ->outlined()
                ->icon('heroicon-o-plus-circle')
                ->color(Color::Emerald),

                // TODO: Enrollment Siswa ke Kelas Tahun Pelajaran
            Action::make('enrollment')
                ->label('Enrollment')
                ->outlined()
                ->visible(fn () =>
                    Siswa::exists() &&
                    KelasTahunPelajaran::exists()
                )
                ->icon('heroicon-o-building-library')
                ->color(Color::Cyan)
                ->requiresConfirmation()
                ->form([
                    Select::make('kelas_tahun_pelajaran_id')
                        ->label('Kelas Aktif')
                        ->options(fn () =>
                            KelasTahunPelajaran::with('kelas', 'tahunPelajaran')
                            ->where('tahun_pelajaran_id', TahunPelajaran::where('status', true)->first()?->id)
                                ->get()
                                ->mapWithKeys(function ($ktp) {
                                    $label = ($ktp->kelas?->nama ?? 'Tanpa Kelas')
                                        . ' - '
                                        . ($ktp->tahunPelajaran?->nama ?? 'Tanpa Tahun');
                                    return [$ktp->id => $label];
                                })
                        )
                        ->searchable()
                        ->required(),

                    Select::make('siswa_id')
                        ->label('Siswa')
                        ->multiple()
                        ->options(function () {
                            $tahunAktifId = TahunPelajaran::where('status', true)->first()?->id;

                            if (! $tahunAktifId) {
                                return [];
                            }

                            // Ambil semua siswa yang SUDAH terdaftar di tahun aktif
                            $sudahTerdaftar = KelasSiswa::whereHas('kelasTahunPelajaran', function ($query) use ($tahunAktifId) {
                                $query->where('tahun_pelajaran_id', $tahunAktifId);
                            })->pluck('siswa_id');

                            // Ambil siswa yang BELUM terdaftar
                            return Siswa::with('user')
                                ->whereNotIn('id', $sudahTerdaftar)
                                ->get()
                                ->mapWithKeys(function ($siswa) {
                                    $label = ($siswa->user?->name ?? 'Tanpa Nama') . ' (' . $siswa->nisn . ')';
                                    return [$siswa->id => $label];
                                });
                        })
                        ->searchable()
                        ->required(),

                ])
                ->action(function (array $data) {
                    $kelasTahunPelajaranId = $data['kelas_tahun_pelajaran_id'];
                    $siswaIds = $data['siswa_id'];

                    $baru = collect($siswaIds)->filter(function ($siswaId) use ($kelasTahunPelajaranId) {
                        return ! KelasSiswa::where('kelas_tahun_pelajaran_id', $kelasTahunPelajaranId)
                            ->where('siswa_id', $siswaId)
                            ->exists();
                    });

                    foreach ($baru as $siswaId) {
                        KelasSiswa::create([
                            'kelas_tahun_pelajaran_id' => $kelasTahunPelajaranId,
                            'siswa_id' => $siswaId,
                        ]);
                    }

                    if ($baru->isEmpty()) {
                        Notification::make()
                            ->title('Tidak Ada Siswa Baru')
                            ->body('Semua siswa sudah terdaftar di kelas tersebut.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Enrollment Berhasil')
                        ->body('Jumlah siswa yang di-enroll: ' . $baru->count())
                        ->success()
                        ->send();
                }),
        ];
    }
}
