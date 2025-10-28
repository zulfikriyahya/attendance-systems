<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi untuk membuat tabel siswas.
     */
    public function up(): void
    {
        Schema::create('siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel users (setiap siswa punya akun user)

            $table->string('rfid')->unique();
            // Nomor kartu RFID unik untuk absensi

            $table->string('nisn')->unique();
            // Nomor Induk Siswa Nasional, harus unik

            $table->foreignUuid('jabatan_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel jabatans (❓ agak aneh untuk siswa → mungkin maksudnya ke tabel kelas?)

            $table->string('jenisKelamin');
            // Jenis kelamin siswa (sebaiknya enum: 'Laki-laki', 'Perempuan')

            $table->string('telepon');
            // Nomor telepon siswa (atau orang tua/wali)

            $table->text('alamat')->nullable();
            // Alamat tempat tinggal

            $table->boolean('status')->default(true);
            // Status aktif/nonaktif siswa

            $table->timestamps();
            // Kolom created_at & updated_at

            $table->softDeletes();
            // Kolom deleted_at untuk soft delete

            $table->index(['rfid']);
            // Index tambahan untuk mempercepat pencarian berdasarkan RFID
        });
    }

    /**
     * Rollback migrasi → hapus tabel siswas.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
