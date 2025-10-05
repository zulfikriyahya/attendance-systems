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
        Schema::create('guru_mata_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID
            // Relasi ke tabel kelas tahun pelajaran
            $table->foreignUuid('mata_pelajaran_id')
                ->constrained('mata_pelajarans')
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
            // Unik kombinasi (mata_pelajarans, pegawais) agar tidak ada duplikasi
            $table->unique(['mata_pelajaran_id', 'pegawai_id'], 'guru_mata_pelajaran_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guru_mata_pelajarans');
    }
};
