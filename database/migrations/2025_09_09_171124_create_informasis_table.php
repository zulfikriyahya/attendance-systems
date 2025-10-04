<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Jalankan migrasi untuk membuat tabel informasis
     */
    public function up(): void
    {
        Schema::create('informasis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Primary key menggunakan UUID

            $table->string('judul');
            // Judul informasi

            $table->text('isi');
            // Isi atau konten informasi

            $table->datetime('tanggal');
            // Tanggal informasi dibuat/dipublikasikan

            $table->enum('status', ['Draft', 'Publish', 'Archive'])
                ->default('Draft');
            // Status informasi: Draft (belum publish), Publish (ditampilkan), Archive (diarsipkan)


            $table->foreignUuid('jabatan_id')
                ->constrained('jabatans')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->string('lampiran')->nullable();
            // Lampiran file opsional (misalnya PDF, gambar, dll.)

            $table->softDeletes();
            // Soft delete agar data tidak langsung hilang permanen

            $table->timestamps();
            // Kolom created_at dan updated_at
        });
    }

    /**
     * Rollback migrasi (hapus tabel)
     */
    public function down(): void
    {
        Schema::dropIfExists('informasis');
    }
};
