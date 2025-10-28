<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel kelas.
     */
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key dengan UUID

            $table->string('nama');
            // Nama kelas, contoh: "X IPA 1", "XI IPS 2"

            $table->text('deskripsi')->nullable();
            // Deskripsi tambahan (opsional), misalnya keterangan kelas

            $table->foreignUuid('jurusan_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel jurusans
            // Jika jurusan dihapus, maka kelas juga ikut terhapus

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete
        });
    }

    /**
     * Rollback migrasi → menghapus tabel kelas.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
