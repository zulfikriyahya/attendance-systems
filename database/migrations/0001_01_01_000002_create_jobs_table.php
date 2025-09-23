<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel jobs → menyimpan antrian job yang menunggu untuk dieksekusi
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); // Primary key auto increment
            $table->string('queue')->index(); // Nama queue (misalnya: default, emails, whatsapp)
            $table->longText('payload'); // Data job yang disimpan (dalam bentuk JSON)
            $table->unsignedTinyInteger('attempts'); // Jumlah percobaan eksekusi job
            $table->unsignedInteger('reserved_at')->nullable(); // Waktu job sedang diproses (timestamp)
            $table->unsignedInteger('available_at'); // Waktu job tersedia untuk dieksekusi
            $table->unsignedInteger('created_at'); // Waktu job dibuat
        });

        // Tabel job_batches → menyimpan informasi batch jobs (fitur Laravel Batch Processing)
        Schema::create('job_batches', function (Blueprint $table) {
            $table->uuid('id')->primary(); // ID unik untuk batch
            $table->string('name'); // Nama batch
            $table->integer('total_jobs'); // Total job dalam batch
            $table->integer('pending_jobs'); // Job yang masih menunggu
            $table->integer('failed_jobs'); // Job yang gagal
            $table->longText('failed_job_ids'); // ID dari job-job yang gagal
            $table->mediumText('options')->nullable(); // Opsi tambahan untuk batch
            $table->integer('cancelled_at')->nullable(); // Waktu batch dibatalkan
            $table->integer('created_at'); // Waktu batch dibuat
            $table->integer('finished_at')->nullable(); // Waktu batch selesai
        });

        // Tabel failed_jobs → menyimpan job yang gagal dieksekusi
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('uuid')->unique(); // UUID unik untuk job
            $table->text('connection'); // Nama koneksi queue (misalnya: database, redis, sqs)
            $table->text('queue'); // Nama queue
            $table->longText('payload'); // Data job yang gagal
            $table->longText('exception'); // Detail error/exception yang terjadi
            $table->timestamp('failed_at')->useCurrent(); // Waktu job gagal
        });
    }

    public function down(): void
    {
        // Menghapus tabel saat rollback migration
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};
