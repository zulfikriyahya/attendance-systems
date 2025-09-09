<?php

use App\Http\Controllers\PresensiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.secret'])->prefix('presensi')->name('presensi.')->group(function () {

    // Core presensi endpoints
    Route::post('/', [PresensiController::class, 'store'])->name('store');
    Route::post('/rfid', [PresensiController::class, 'store'])->name('rfid'); // Alias untuk backward compatibility

    // Validation & Status
    Route::post('/validate', [PresensiController::class, 'validateRfid'])->name('validate');
    Route::get('/status/{rfid}', [PresensiController::class, 'getStatusPresensi'])->name('status');

    // Schedule
    Route::get('/jadwal', [PresensiController::class, 'getJadwalHariIni'])->name('jadwal');

    // Bulk operations
    Route::post('/sync-bulk', [PresensiController::class, 'syncBulk'])
        ->middleware('throttle:bulk-sync') // Rate limiting untuk bulk operations
        ->name('sync.bulk');

    // Health & Monitoring
    Route::get('/health', [PresensiController::class, 'health'])->name('health');
    Route::get('/ping', function () {
        return response()->json([
            'status' => 'ok',
            'message' => 'API Presensi RFID aktif',
            'timestamp' => now()->toISOString(),
            'version' => '0.1.1',
        ]);
    })->name('ping');

    // Device & Statistics
    Route::get('/device/stats', [PresensiController::class, 'deviceStats'])
        ->middleware('throttle:device-stats')
        ->name('device.stats');

    // Advanced endpoints (optional)
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('/summary', [PresensiController::class, 'getDailySummary'])->name('admin.summary');
        Route::get('/export', [PresensiController::class, 'exportPresensi'])->name('admin.export');
    });
});
