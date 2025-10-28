<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            // Primary key auto increment

            $table->string('nomor');
            // Nomor tujuan WhatsApp (bisa nomor siswa/pegawai)

            $table->string('type');
            // Jenis pesan, contoh: 'presensi', 'informasi'

            $table->string('status');
            // Status pengiriman: 'success' atau 'failed'

            $table->text('message');
            // Isi pesan yang dikirim

            $table->text('response')->nullable();
            // Response dari API WhatsApp (jika ada)

            $table->text('error_message')->nullable();
            // Pesan error jika gagal mengirim

            $table->timestamp('sent_at')->nullable();
            // Waktu pesan dikirim

            $table->timestamps();
            // Kolom created_at & updated_at

            // Index untuk mempercepat pencarian
            $table->index(['nomor', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
