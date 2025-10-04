<?php

namespace App\Http\Controllers;

use App\Models\PengajuanKartu;

class CetakKartuController extends Controller
{
    public function index()
    {
        $pengajuans = PengajuanKartu::where('status', 'Proses')->get();

        return view('cetak.kartu', compact('pengajuans'));
    }
}
