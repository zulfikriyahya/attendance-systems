<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel presensi_pegawais.
     */
    public function up(): void
    {
        Schema::create('presensi_pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->foreignUuid('pegawai_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel pegawais

            $table->time('jamDatang')->nullable();
            // Jam pegawai datang

            $table->time('jamPulang')->nullable();
            // Jam pegawai pulang

            $table->date('tanggal')->nullable();
            // Tanggal presensi

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
            // Status kehadiran pegawai

            $table->enum('statusPulang', [
                'Pulang',
                'Pulang Sebelum Waktunya',
                'Mangkir',
            ])->nullable();
            // Status kepulangan pegawai

            $table->boolean('is_synced')->default(false);
            // Menandai apakah data sudah disinkronkan dengan perangkat lain

            $table->timestamp('synced_at')->nullable();
            // Waktu sinkronisasi terakhir

            $table->string('device_id')->nullable();
            // ID perangkat absensi yang digunakan

            $table->json('sync_metadata')->nullable();
            // Metadata tambahan saat sinkronisasi (misalnya info server, lokasi, dsb.)

            $table->text('catatan')->nullable();
            // Catatan tambahan terkait presensi

            $table->enum('statusApproval', ['pending', 'approved', 'rejected'])->nullable();
            // Status approval jika ada permohonan (misalnya izin, cuti, atau koreksi presensi)

            $table->string('berkasLampiran')->nullable();
            // Lampiran file (misalnya surat izin/cuti)

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete

            // Indexing
            $table->index(['is_synced', 'created_at']);
            // Mempercepat query data sinkronisasi berdasarkan waktu

            $table->index('device_id');
            // Mempercepat pencarian berdasarkan perangkat absensi

            $table->index(['tanggal', 'pegawai_id']);
            // Mempercepat query presensi per pegawai per tanggal
        });
    }

    /**
     * Rollback migrasi → hapus tabel presensi_pegawais.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_pegawais');
    }
};
