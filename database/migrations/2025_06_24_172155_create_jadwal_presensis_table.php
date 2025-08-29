<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_presensis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instansi_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->string('hari')->nullable();
            $table->time('jamDatang')->nullable();
            $table->time('jamPulang')->nullable();
            $table->boolean('status');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_presensis');
    }
};
