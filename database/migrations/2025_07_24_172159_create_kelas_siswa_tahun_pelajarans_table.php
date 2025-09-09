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
        Schema::create('kelas_siswa_tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->foreignUuid('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignUuid('tahun_pelajaran_id')->constrained()->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kelas_id', 'siswa_id', 'tahun_pelajaran_id'], 'kelas_siswa_tahun_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_siswa_tahun_pelajarans');
    }
};
