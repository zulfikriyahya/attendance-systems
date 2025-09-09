<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanSinglePegawaiController extends Controller
{
    public function print(Request $request, Pegawai $pegawai)
    {
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $presensis = $pegawai->presensiPegawai()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $pdf = Pdf::loadView('exports.presensi-pegawai', [
            'pegawai' => $pegawai,
            'presensis' => $presensis,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);

        return $pdf->stream("Presensi {$pegawai->user->name} {$bulan} {$tahun}.pdf");
    }
}
