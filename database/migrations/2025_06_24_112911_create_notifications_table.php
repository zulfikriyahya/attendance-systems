<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel notifications → menyimpan semua notifikasi yang dikirim ke user
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // ID unik berbentuk UUID untuk setiap notifikasi
            $table->string('type'); // Jenis notifikasi (class notifikasi yang digunakan)
            $table->morphs('notifiable');
            // notifiable_type (nama model) & notifiable_id (ID dari model tersebut)
            // Contoh: notifikasi bisa dikirim ke model User, Admin, Siswa, dsb.

            $table->text('data'); // Data notifikasi (biasanya dalam format JSON)
            $table->timestamp('read_at')->nullable(); // Waktu notifikasi dibaca (null = belum dibaca)
            $table->timestamps(); // Kolom created_at & updated_at
        });
    }

    public function down(): void
    {
        // Menghapus tabel jika migration di-rollback
        Schema::dropIfExists('notifications');
    }
};
