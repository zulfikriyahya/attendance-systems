<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_mata_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mata_pelajaran_id')
                ->constrained('mata_pelajarans')
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
            $table->unique(['mata_pelajaran_id', 'pegawai_id'], 'guru_mata_pelajaran_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_mata_pelajarans');
    }
};
