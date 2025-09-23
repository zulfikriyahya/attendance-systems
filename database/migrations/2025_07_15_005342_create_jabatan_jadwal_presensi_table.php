<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel pivot jabatan_jadwal_presensi.
     * Tabel ini menghubungkan relasi Many-to-Many antara jabatan dan jadwal_presensi.
     */
    public function up(): void
    {
        Schema::create('jabatan_jadwal_presensi', function (Blueprint $table) {
            // Foreign key ke tabel jabatans
            $table->foreignUuid('jabatan_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Foreign key ke tabel jadwal_presensis
            $table->foreignUuid('jadwal_presensi_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps(); // created_at & updated_at

            // Primary key gabungan untuk menghindari duplikasi relasi
            $table->primary(['jabatan_id', 'jadwal_presensi_id']);
        });
    }

    /**
     * Rollback migrasi dengan menghapus tabel pivot jabatan_jadwal_presensi.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatan_jadwal_presensi');
    }
};
