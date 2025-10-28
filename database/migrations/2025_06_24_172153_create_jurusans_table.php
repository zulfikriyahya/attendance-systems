<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel jurusans.
     */
    public function up(): void
    {
        Schema::create('jurusans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key dengan UUID

            $table->foreignUuid('instansi_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel instansis
            // Jika instansi dihapus, jurusan ikut terhapus

            $table->string('nama');
            // Nama jurusan, contoh: "IPA", "IPS", "Teknik Informatika"

            $table->text('deskripsi')->nullable();
            // Deskripsi tambahan (opsional)

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete
        });
    }

    /**
     * Rollback migrasi → menghapus tabel jurusans.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurusans');
    }
};
