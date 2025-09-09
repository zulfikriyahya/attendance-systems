<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instansis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama');
            $table->string('nss')->nullable();
            $table->string('npsn')->nullable();
            $table->string('logoInstansi')->nullable();
            $table->string('logoInstitusi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('pimpinan')->nullable();
            $table->string('ttePimpinan')->nullable();
            $table->string('nipPimpinan')->nullable();
            $table->string('akreditasi')->nullable();
            $table->enum('status', ['Negeri', 'Swasta'])->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instansis');
    }
};
