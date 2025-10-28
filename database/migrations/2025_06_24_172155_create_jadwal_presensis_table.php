<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel jadwal_presensis.
     */
    public function up(): void
    {
        Schema::create('jadwal_presensis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->foreignUuid('instansi_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel instansis (jadwal presensi berlaku untuk instansi tertentu)

            $table->string('nama');
            // Nama jadwal, contoh: "Presensi Pagi", "Presensi Siang"

            $table->text('deskripsi')->nullable();
            // Deskripsi tambahan (opsional)

            $table->string('hari')->nullable();
            // Hari presensi, misalnya: "Senin", "Selasa", atau "Weekdays"

            $table->time('jamDatang')->nullable();
            // Jam datang presensi, misalnya 07:30

            $table->time('jamPulang')->nullable();
            // Jam pulang presensi, misalnya 16:00

            $table->boolean('status');
            // Status jadwal: aktif (true) / nonaktif (false)

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete

            $table->index(['status', 'hari']);
            // Index gabungan → mempercepat query pencarian jadwal berdasarkan status & hari
        });
    }

    /**
     * Rollback migrasi → menghapus tabel jadwal_presensis.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_presensis');
    }
};
