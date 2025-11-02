<?php

namespace App\Http\Controllers;

use App\Models\PresensiPegawai;
use App\Models\Pegawai;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanAllPegawaiController extends Controller
{
    public function printAll(Request $request)
    {
        $bulan = $request->get('bulan');
        $tahun = $request->get('tahun');

        $presensis = PresensiPegawai::whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get()
            ->groupBy('pegawai_id');

        $pegawaiIdsDenganPresensi = $presensis->keys();

        $pegawais = Pegawai::with(['user', 'jabatan.instansi'])
            ->where(function ($query) use ($pegawaiIdsDenganPresensi) {
                $query->where('status', true)
                    ->orWhereIn('id', $pegawaiIdsDenganPresensi);
            })
            ->get()
            ->sortBy(fn($pegawai) => $pegawai->user->name);

        $pdf = Pdf::loadView('exports.presensi-pegawai-batch', [
            'pegawais' => $pegawais,
            'presensis' => $presensis,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ])->setPaper('A4', 'portrait');

        return $pdf->download("Laporan Presensi Pegawai {$bulan} {$tahun}.pdf");
    }
}
