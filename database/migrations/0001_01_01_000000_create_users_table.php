<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Membuat tabel users
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key (auto increment)
            $table->string('name')->required(); // Nama user (wajib diisi)
            $table->string('username')->required()->unique(); // Username unik (wajib diisi)
            $table->string('email')->required()->unique(); // Email unik (wajib diisi)
            $table->timestamp('email_verified_at')->nullable()->useCurrent(); // Waktu verifikasi email (boleh null, default waktu saat ini)
            $table->string('password')->required(); // Password user (wajib diisi)
            $table->string('avatar')->nullable(); // Foto profil user (boleh kosong)
            $table->boolean('status')->required(); // Status aktif/nonaktif (wajib diisi)
            $table->rememberToken(); // Token untuk "remember me" (login tetap)
            $table->timestamps(); // Kolom created_at & updated_at
            $table->softDeletes(); // Kolom deleted_at untuk soft delete
        });

        // Membuat tabel password_reset_tokens
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Email sebagai primary key
            $table->string('token'); // Token reset password
            $table->timestamp('created_at')->nullable(); // Waktu token dibuat
        });

        // Membuat tabel sessions (untuk menyimpan sesi user)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // ID sesi (string unik)
            $table->foreignId('user_id')->nullable()->index(); // Relasi ke user (boleh kosong), diindex
            $table->string('ip_address', 45)->nullable(); // Alamat IP (max 45 karakter untuk IPv6)
            $table->text('user_agent')->nullable(); // Informasi browser/device user
            $table->longText('payload'); // Data sesi yang disimpan
            $table->integer('last_activity')->index(); // Waktu aktivitas terakhir (timestamp), diindex
        });
    }

    public function down(): void
    {
        // Menghapus tabel jika rollback
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
