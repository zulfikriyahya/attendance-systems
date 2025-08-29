<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('presensi_pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pegawai_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->time('jamDatang')->nullable();
            $table->time('jamPulang')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('statusPresensi', [
                'Hadir',
                'Terlambat',
                'Alfa',
                'Izin',
                'Cuti',
                'Izin Terlambat',
                'Dinas Luar',
                'Sakit',
                'Libur',
            ])->nullable();
            $table->enum('statusPulang', [
                'Pulang',
                'Pulang Sebelum Waktunya',
                'Mangkir',
            ])->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->string('device_id')->nullable();
            $table->json('sync_metadata')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('statusApproval', ['pending', 'approved', 'rejected'])->nullable();
            $table->string('berkasLampiran')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_synced', 'created_at']);
            $table->index('device_id');
            $table->index(['tanggal', 'pegawai_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_pegawais');
    }
};
