<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel kelas_siswa_tahun_pelajarans.
     * Tabel ini berfungsi sebagai penghubung relasi antara kelas, siswa, dan tahun pelajaran.
     * Dengan tabel ini, kita bisa melacak siswa masuk ke kelas tertentu pada tahun pelajaran tertentu.
     */
    public function up(): void
    {
        Schema::create('kelas_siswa_tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID

            // Relasi ke tabel kelas
            $table->foreignUuid('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();

            // Relasi ke tabel siswa
            $table->foreignUuid('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete();

            // Relasi ke tabel tahun pelajaran
            $table->foreignUuid('tahun_pelajaran_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at untuk soft delete

            // Unik kombinasi (kelas, siswa, tahun) agar tidak ada duplikasi
            $table->unique(['kelas_id', 'siswa_id', 'tahun_pelajaran_id'], 'kelas_siswa_tahun_unique');
        });
    }

    /**
     * Rollback migrasi dengan menghapus tabel kelas_siswa_tahun_pelajarans.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_siswa_tahun_pelajarans');
    }
};
