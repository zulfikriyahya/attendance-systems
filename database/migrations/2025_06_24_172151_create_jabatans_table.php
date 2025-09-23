<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel jabatans.
     */
    public function up(): void
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key berupa UUID (unik untuk setiap jabatan)

            $table->foreignUuid('instansi_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel instansis (satu jabatan dimiliki oleh instansi tertentu)
            // Jika instansi dihapus, jabatan juga ikut terhapus
            // Jika instansi diupdate (UUID berubah), ikut terupdate

            $table->string('nama');
            // Nama jabatan (contoh: Kepala Sekolah, Wakil, Guru, Staff)

            $table->text('deskripsi')->nullable();
            // Deskripsi detail jabatan (opsional)

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at (soft delete)
        });
    }

    /**
     * Rollback migrasi → menghapus tabel jabatans.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatans');
    }
};
