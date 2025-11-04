<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kelas_tahun_pelajaran_id')
                ->constrained('kelas_tahun_pelajarans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();
            $table->foreignUuid('siswa_id')
                ->constrained('siswas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['kelas_tahun_pelajaran_id', 'siswa_id'], 'kelas_siswas_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas_siswas');
    }
};
