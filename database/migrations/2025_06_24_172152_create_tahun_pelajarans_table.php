<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel tahun_pelajarans.
     */
    public function up(): void
    {
        Schema::create('tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key dengan UUID

            $table->foreignUuid('instansi_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel instansis
            // Jika instansi dihapus, semua tahun pelajarannya ikut terhapus

            $table->string('nama');
            // Nama tahun pelajaran, contoh: "2024/2025"

            $table->date('mulai')->nullable();
            // Tanggal mulai tahun pelajaran (opsional)

            $table->date('selesai')->nullable();
            // Tanggal selesai tahun pelajaran (opsional)

            $table->text('deskripsi')->nullable();
            // Keterangan tambahan (opsional)

            $table->boolean('status');
            // Status aktif/non-aktif
            // true → aktif, false → tidak aktif

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete
        });
    }

    /**
     * Rollback migrasi → menghapus tabel tahun_pelajarans.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahun_pelajarans');
    }
};
