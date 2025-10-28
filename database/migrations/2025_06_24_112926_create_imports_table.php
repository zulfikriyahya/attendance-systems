<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel imports.
     */
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id(); // Primary key (ID auto increment)

            $table->timestamp('completed_at')->nullable();
            // Waktu proses import selesai (null = belum selesai)

            $table->string('file_name');
            // Nama file yang diimpor (contoh: siswa.xlsx)

            $table->string('file_path');
            // Path lokasi file disimpan di server

            $table->string('importer');
            // Nama class/tipe importer yang digunakan (misal: SiswaImporter)

            $table->unsignedInteger('processed_rows')->default(0);
            // Jumlah baris yang sudah diproses

            $table->unsignedInteger('total_rows');
            // Total baris data dalam file

            $table->unsignedInteger('successful_rows')->default(0);
            // Jumlah baris yang berhasil diimpor

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Relasi ke tabel users (user yang melakukan import)
            // Jika user dihapus, data import juga ikut terhapus

            $table->timestamps();
            // Kolom created_at & updated_at
        });
    }

    /**
     * Rollback migrasi → menghapus tabel imports.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
