<?php

use App\Http\Controllers\CetakKartuController;
use App\Http\Controllers\LaporanAllPegawaiController;
use App\Http\Controllers\LaporanAllSiswaController;
use App\Http\Controllers\LaporanSinglePegawaiController;
use App\Http\Controllers\LaporanSingleSiswaController;
use App\Http\Controllers\VerifikasiLaporanPegawaiController;
use App\Http\Controllers\VerifikasiLaporanSiswaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/laporan/pegawai/all', [LaporanAllPegawaiController::class, 'printAll'])
        ->name('laporan.all.pegawai');

    Route::get('/laporan/pegawai/{pegawai}', [LaporanSinglePegawaiController::class, 'print'])
        ->name('laporan.single.pegawai');

    Route::get('/laporan/pegawai/verifikasi/{id}', [VerifikasiLaporanPegawaiController::class, 'verifikasi'])
        ->name('laporan.pegawai.verifikasi');

    Route::get('/siswa/{siswa}/presensi/print', [LaporanSingleSiswaController::class, 'print'])
        ->name('laporan.single.siswa');

    Route::get('/laporan/siswa/print-all', [LaporanAllSiswaController::class, 'printAll'])
        ->name('laporan.all.siswa');

    Route::get('/laporan/siswa/verifikasi/{id}', [VerifikasiLaporanSiswaController::class, 'verifikasi'])
        ->name('laporan.siswa.verifikasi');

    Route::get('/cetak-kartu', [CetakKartuController::class, 'index'])
        ->name('cetak-kartu');
});
