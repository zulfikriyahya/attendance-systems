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
        Schema::create('tahun_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instansi_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama');
            $table->date('mulai')->nullable();
            $table->date('selesai')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahun_pelajarans');
    }
};
