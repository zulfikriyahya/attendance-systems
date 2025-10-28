<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel presensi_siswas.
     */
    public function up(): void
    {
        Schema::create('presensi_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key dengan tipe UUID

            $table->foreignUuid('siswa_id') // Relasi ke tabel siswas
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->date('tanggal')->nullable(); // Tanggal presensi
            $table->time('jamDatang')->nullable(); // Jam kedatangan siswa
            $table->time('jamPulang')->nullable(); // Jam pulang siswa

            $table->enum('statusPresensi', [ // Status kehadiran siswa
                'Hadir',
                'Terlambat',
                'Alfa',
                'Izin',
                'Sakit',
                'Libur',
                'Dispen',
            ])->nullable();

            $table->enum('statusPulang', [ // Status kepulangan siswa
                'Pulang',
                'Pulang Sebelum Waktunya',
                'Bolos',
            ])->nullable();

            $table->boolean('is_synced')->default(false); // Status sinkronisasi data
            $table->timestamp('synced_at')->nullable(); // Waktu terakhir sinkronisasi
            $table->string('device_id')->nullable(); // ID perangkat absensi (jika ada)
            $table->json('sync_metadata')->nullable(); // Data tambahan dari proses sinkronisasi
            $table->text('catatan')->nullable(); // Catatan tambahan terkait presensi
            $table->enum('statusApproval', ['pending', 'approved', 'rejected'])->nullable(); // Status persetujuan (misalnya untuk izin/dispensasi)
            $table->string('berkasLampiran')->nullable(); // Path/URL lampiran file terkait (opsional)

            $table->timestamps(); // created_at & updated_at
            $table->softDeletes(); // deleted_at untuk soft delete

            // Index untuk optimasi query
            $table->index(['is_synced', 'created_at']);
            $table->index('device_id');
            $table->index(['tanggal', 'siswa_id']);
        });
    }

    /**
     * Rollback migrasi dengan menghapus tabel presensi_siswas.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_siswas');
    }
};
