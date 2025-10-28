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
        Schema::create('kelas_pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID

            // Relasi ke tabel kelas tahun pelajaran
            $table->foreignUuid('kelas_tahun_pelajaran_id')
                ->constrained('kelas_tahun_pelajarans')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            // Relasi ke tabel pegawai
            $table->foreignUuid('pegawai_id')
                ->constrained('pegawais')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->nullable();

            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at untuk soft delete

            // Unik kombinasi (kelas_tahun_pelajarans, pegawais) agar tidak ada duplikasi
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
