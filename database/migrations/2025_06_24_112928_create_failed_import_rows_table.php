<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel failed_import_rows.
     */
    public function up(): void
    {
        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->id(); // Primary key (ID auto increment)

            $table->json('data');
            // Data baris yang gagal diimpor (dalam format JSON)

            $table->foreignId('import_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel imports (setiap baris gagal terkait dengan proses import tertentu)
            // Jika import dihapus, baris gagal juga ikut terhapus

            $table->text('validation_error')->nullable();
            // Pesan error validasi kenapa baris ini gagal (misalnya: email tidak valid, NIS sudah ada, dsb.)

            $table->timestamps();
            // Kolom created_at & updated_at
        });
    }

    /**
     * Rollback migrasi → menghapus tabel failed_import_rows.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
    }
};
