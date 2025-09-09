<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;

class VerifikasiLaporanSiswaController extends Controller
{
    public function verifikasi($id, Request $request)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $siswa = Siswa::with(['user', 'jabatan'])->findOrFail($id);

        $presensis = $siswa->presensiSiswa()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        $siswa = Siswa::with(['user', 'jabatan.instansi'])->findOrFail($id);

        return view('laporan.verifikasi-siswa', compact('siswa', 'presensis', 'bulan', 'tahun'));
    }
}
