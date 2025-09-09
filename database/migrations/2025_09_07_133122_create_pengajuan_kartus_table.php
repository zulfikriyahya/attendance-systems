<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_kartus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nomorPengajuanKartu')->unique();
            $table->datetime('tanggalPengajuanKartu');
            $table->text('alasanPengajuanKartu');
            $table->enum('status', ['Pending', 'Proses', 'Selesai'])->default('Pending');
            $table->softDeletes();
            $table->timestamps();

            // Indexes untuk performance
            $table->index(['user_id', 'status']);
            $table->index('nomorPengajuan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_kartus');
    }
};
