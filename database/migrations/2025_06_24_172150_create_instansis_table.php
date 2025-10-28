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
            // ID unik untuk instansi (gunakan UUID agar lebih aman & fleksibel)

            $table->string('nama');
            // Nama instansi (contoh: MTs Negeri 1 Pandeglang)

            $table->string('nss')->nullable();
            // Nomor Statistik Sekolah (opsional)

            $table->string('npsn')->nullable();
            // Nomor Pokok Sekolah Nasional (opsional)

            $table->string('logoInstansi')->nullable();
            // Logo utama instansi (misalnya untuk header dokumen)

            $table->string('logoInstitusi')->nullable();
            // Logo tambahan (misalnya logo Kemenag/Kemdikbud)

            $table->text('alamat')->nullable();
            // Alamat lengkap instansi

            $table->string('telepon')->nullable();
            // Nomor telepon kantor

            $table->string('email')->nullable();
            // Email resmi instansi

            $table->string('pimpinan')->nullable();
            // Nama pimpinan (Kepala sekolah/madrasah)

            $table->string('ttePimpinan')->nullable();
            // Tanda Tangan Elektronik pimpinan (jika ada)

            $table->string('nipPimpinan')->nullable();
            // NIP pimpinan (jika ada)

            $table->string('akreditasi')->nullable();
            // Status akreditasi sekolah (contoh: A, B, C)

            $table->enum('status', ['Negeri', 'Swasta'])->nullable();
            // Status kepemilikan instansi

            $table->string('website')->nullable();
            // Website resmi instansi

            $table->timestamps();
            // created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete
        });
    }

    public function down(): void
    {
        // Rollback → hapus tabel instansis
        Schema::dropIfExists('instansis');
    }
};
