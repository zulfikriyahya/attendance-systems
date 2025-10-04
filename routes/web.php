<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CetakKartuController;
use App\Http\Controllers\LaporanAllSiswaController;
use App\Http\Controllers\LaporanAllPegawaiController;
use App\Http\Controllers\LaporanSingleSiswaController;
use App\Http\Controllers\LaporanSinglePegawaiController;
use App\Http\Controllers\VerifikasiLaporanSiswaController;
use App\Http\Controllers\VerifikasiLaporanPegawaiController;

Route::get('/', function () {
    return view('index');
});

// Pegawai
Route::get('/pegawai/{pegawai}/presensi/print', [LaporanSinglePegawaiController::class, 'print'])
    ->name('laporan.single.pegawai');

Route::get('/laporan/pegawai/print-all', [LaporanAllPegawaiController::class, 'printAll'])
    ->name('laporan.all.pegawai');

Route::get('/laporan/pegawai/verifikasi/{id}', [VerifikasiLaporanPegawaiController::class, 'verifikasi'])
    ->name('laporan.pegawai.verifikasi');

// Siswa
Route::get('/siswa/{siswa}/presensi/print', [LaporanSingleSiswaController::class, 'print'])
    ->name('laporan.single.siswa');

Route::get('/laporan/siswa/print-all', [LaporanAllSiswaController::class, 'printAll'])
    ->name('laporan.all.siswa');

Route::get('/laporan/siswa/verifikasi/{id}', [VerifikasiLaporanSiswaController::class, 'verifikasi'])
    ->name('laporan.siswa.verifikasi');

// Cetak Pengajuan Kartu
Route::get('/cetak-kartu', [CetakKartuController::class, 'index'])
    ->name('cetak-kartu');
