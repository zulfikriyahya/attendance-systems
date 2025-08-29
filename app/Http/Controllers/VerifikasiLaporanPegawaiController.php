<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;

class VerifikasiLaporanPegawaiController extends Controller
{
    public function verifikasi($id, Request $request)
    {
        $bulan = $request->get('bulan', now()->month);
        $tahun = $request->get('tahun', now()->year);

        $pegawai = Pegawai::with(['user', 'jabatan'])->findOrFail($id);

        $presensis = $pegawai->presensiPegawai()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        $pegawai = Pegawai::with(['user', 'jabatan.instansi'])->findOrFail($id);

        return view('laporan.verifikasi-pegawai', compact('pegawai', 'presensis', 'bulan', 'tahun'));
    }
}
