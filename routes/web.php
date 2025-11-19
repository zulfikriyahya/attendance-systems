<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CetakKartuController;
use App\Http\Controllers\LaporanSingleSiswaController;
use App\Http\Controllers\LaporanSinglePegawaiController;
use App\Http\Controllers\VerifikasiLaporanSiswaController;
use App\Http\Controllers\VerifikasiLaporanPegawaiController;

Route::get('/', function () {
    return view('index');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/laporan/pegawai/{pegawai}', [LaporanSinglePegawaiController::class, 'print'])
        ->name('laporan.single.pegawai');

    Route::get('/siswa/{siswa}/presensi/print', [LaporanSingleSiswaController::class, 'print'])
        ->name('laporan.single.siswa');

    Route::get('/cetak-kartu', [CetakKartuController::class, 'index'])
        ->name('cetak-kartu');
});

Route::get('/laporan/pegawai/verifikasi/{id}', [VerifikasiLaporanPegawaiController::class, 'verifikasi'])
    ->name('laporan.pegawai.verifikasi');

Route::get('/laporan/siswa/verifikasi/{id}', [VerifikasiLaporanSiswaController::class, 'verifikasi'])
    ->name('laporan.siswa.verifikasi');
