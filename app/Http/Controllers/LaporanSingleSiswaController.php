<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanSingleSiswaController extends Controller
{
    public function print(Request $request, Siswa $siswa)
    {
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $presensis = $siswa->presensiSiswa()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $pdf = Pdf::loadView('exports.presensi-siswa', [
            'siswa' => $siswa,
            'presensis' => $presensis,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);

        return $pdf->stream("Presensi {$siswa->user->name} {$bulan} {$tahun}.pdf");
    }
}
