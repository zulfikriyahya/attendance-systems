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
        Schema::create('presensi_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('siswa_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('tanggal')->nullable();
            $table->time('jamDatang')->nullable();
            $table->time('jamPulang')->nullable();

            $table->enum('statusPresensi', [
                'Hadir',
                'Terlambat',
                'Alfa',
                'Izin',
                'Sakit',
                'Libur',
                'Dispen',
            ])->nullable();

            $table->enum('statusPulang', [
                'Pulang',
                'Pulang Sebelum Waktunya',
                'Bolos',
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
            $table->index(['tanggal', 'siswa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_siswas');
    }
};
