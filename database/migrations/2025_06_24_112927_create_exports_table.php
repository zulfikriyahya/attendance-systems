<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel exports.
     */
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id(); // Primary key (ID auto increment)

            $table->timestamp('completed_at')->nullable();
            // Waktu proses export selesai (null = belum selesai)

            $table->string('file_disk');
            // Disk penyimpanan file (misal: local, public, s3)

            $table->string('file_name')->nullable();
            // Nama file hasil export (bisa kosong sebelum selesai)

            $table->string('exporter');
            // Nama class/tipe exporter yang digunakan (misal: SiswaExporter)

            $table->unsignedInteger('processed_rows')->default(0);
            // Jumlah baris yang sudah diproses

            $table->unsignedInteger('total_rows');
            // Total baris data yang diexport

            $table->unsignedInteger('successful_rows')->default(0);
            // Jumlah baris yang berhasil diexport

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel users (user yang melakukan export)
            // Jika user dihapus, data export ikut dihapus
            // Jika user diupdate (misal ID berubah), ikut terupdate

            $table->timestamps();
            // Kolom created_at & updated_at
        });
    }

    /**
     * Rollback migrasi → menghapus tabel exports.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
