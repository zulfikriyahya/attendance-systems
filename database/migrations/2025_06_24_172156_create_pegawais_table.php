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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('rfid')->unique()->required();
            $table->string('nip')->unique()->required();
            $table->string('jenisKelamin')->required();
            $table->string('telepon')->required();
            $table->text('alamat')->nullable();
            $table->foreignUuid('jabatan_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['rfid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
