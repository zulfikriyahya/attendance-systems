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
        Schema::create('kelas_pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kelas_tahun_pelajaran_id')
                ->constrained('kelas_tahun_pelajarans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();
            $table->foreignUuid('pegawai_id')
                ->constrained('pegawais')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['kelas_tahun_pelajaran_id', 'pegawai_id'], 'kelas_pegawais_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_pegawais');
    }
};
