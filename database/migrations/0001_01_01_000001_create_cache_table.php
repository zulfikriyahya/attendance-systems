<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Membuat tabel cache
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary(); // Primary key berupa key (unik)
            $table->mediumText('value'); // Data cache yang disimpan
            $table->integer('expiration'); // Waktu kedaluwarsa cache (dalam bentuk timestamp)
        });

        // Membuat tabel cache_locks
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary(); // Primary key berupa key (unik)
            $table->string('owner'); // Pemilik lock (biasanya ID proses)
            $table->integer('expiration'); // Waktu kedaluwarsa lock (timestamp)
        });
    }

    public function down(): void
    {
        // Menghapus tabel cache dan cache_locks saat rollback
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
