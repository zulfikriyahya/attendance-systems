<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID

            // Relasi ke tabel kelas tahun pelajaran
            $table->foreignUuid('kelas_tahun_pelajaran_id')
                ->constrained('kelas_tahun_pelajarans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            // Relasi ke tabel siswa
            $table->foreignUuid('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at untuk soft delete

            // Unik kombinasi (kelas_tahun_pelajarans, siswas) agar tidak ada duplikasi
            $table->unique(['kelas_tahun_pelajaran_id', 'siswa_id'], 'kelas_siswas_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_siswas');
    }
};
