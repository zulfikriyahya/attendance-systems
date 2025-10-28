<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas_tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID

            // Relasi ke tabel kelas
            $table->foreignUuid('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            // Relasi ke tabel tahun pelajaran
            $table->foreignUuid('tahun_pelajaran_id')
                ->constrained('tahun_pelajarans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at untuk soft delete

            // Unik kombinasi (kelas, tahun_pelajarans) agar tidak ada duplikasi
            $table->unique(['kelas_id', 'tahun_pelajaran_id'], 'kelas_tahun_pelajarans_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_tahun_pelajarans');
    }
};
