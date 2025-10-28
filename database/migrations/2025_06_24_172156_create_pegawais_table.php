<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel pegawais.
     */
    public function up(): void
    {
        Schema::create('pegawais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel users (setiap pegawai punya user account)

            $table->string('rfid')->unique();
            // Nomor kartu RFID unik untuk absensi

            $table->string('nip')->unique();
            // Nomor Induk Pegawai unik

            $table->string('jenisKelamin');
            // Jenis kelamin pegawai (sebaiknya pakai enum: 'Pria', 'Wanita')

            $table->string('telepon');
            // Nomor telepon pegawai (sebaiknya diberi panjang maksimal, misalnya 20 char)

            $table->text('alamat')->nullable();
            // Alamat pegawai (opsional)

            $table->foreignUuid('jabatan_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel jabatans (pegawai punya jabatan tertentu)

            $table->boolean('status')->default(true);
            // Status aktif/nonaktif (default: aktif)

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete

            $table->index(['rfid']);
            // Index tambahan untuk mempercepat pencarian berdasarkan RFID
        });
    }

    /**
     * Rollback migrasi → hapus tabel pegawais.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
