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
        Schema::create('mata_pelajarans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Primary key berbentuk UUID
            $table->string('nama')->unique(); // Nama mata pelajaran, harus unik
            $table->text('deskripsi')->nullable(); // Deskripsi mata pelajaran, boleh
            $table->boolean('status')->default(true); // Status aktif atau tidak, default aktif
            $table->timestamps();   // created_at & updated_at
            $table->softDeletes();  // deleted_at untuk soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};
