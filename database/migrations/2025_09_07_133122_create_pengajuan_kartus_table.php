<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_kartus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Relasi ke tabel users, jika user dihapus maka pengajuan juga terhapus

            $table->string('nomorPengajuanKartu')->unique();
            // Nomor pengajuan unik agar tidak ada duplikasi

            $table->datetime('tanggalPengajuanKartu');
            // Waktu kapan pengajuan dilakukan

            $table->text('alasanPengajuanKartu');
            // Alasan pengajuan kartu (misalnya kartu hilang, rusak, dll.)

            $table->enum('status', ['Pending', 'Proses', 'Selesai'])
                ->default('Pending');
            // Status pengajuan default = Pending

            $table->integer('biaya')->nullable();
            // Biaya pengajuan (opsional)

            $table->boolean('statusAmbil')->default(false);
            // Menandai apakah kartu sudah diambil atau belum

            $table->softDeletes();
            // Soft delete, agar data bisa dipulihkan jika terhapus

            $table->timestamps();
            // created_at dan updated_at

            // Index tambahan untuk optimasi pencarian
            $table->index(['user_id', 'status']);
            $table->index('nomorPengajuanKartu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_kartus');
    }
};
