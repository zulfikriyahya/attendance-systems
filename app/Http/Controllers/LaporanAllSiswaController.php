<?php

namespace App\Http\Controllers;

use App\Models\PresensiSiswa;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanAllSiswaController extends Controller
{
    public function printAll(Request $request)
    {
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $presensis = PresensiSiswa::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy('siswa_id');

        $siswaIdsDenganPresensi = $presensis->keys();

        $siswas = Siswa::with(['user', 'jabatan.instansi'])
            ->where(function ($query) use ($siswaIdsDenganPresensi) {
                $query->where('status', true)
                    ->orWhereIn('id', $siswaIdsDenganPresensi);
            })
            ->get()
            ->sortBy(fn ($siswa) => $siswa->user->name);

        $pdf = Pdf::loadView('exports.presensi-siswa-batch', [
            'siswas' => $siswas,
            'presensis' => $presensis,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ])->setPaper('A4', 'portrait');

        return $pdf->download("Laporan Presensi Siswa {$bulan} {$tahun}.pdf");
    }
}
