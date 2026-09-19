<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Prestasi Siswa ──────────────────────────────────────
        Schema::create('prestasi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->unsignedInteger('point')->default(0);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siswa_id', 'tanggal']);
            $table->index(['sekolah_id', 'tanggal']);
        });

        // ── Pelanggaran Siswa ───────────────────────────────────
        Schema::create('pelanggaran_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->unsignedInteger('point')->default(0);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siswa_id', 'tanggal']);
            $table->index(['sekolah_id', 'tanggal']);
        });

        // ── Prestasi Guru ───────────────────────────────────────
        Schema::create('prestasi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->unsignedInteger('point')->default(0);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['guru_id', 'tanggal']);
            $table->index(['sekolah_id', 'tanggal']);
        });

        // ── Pelanggaran Guru ────────────────────────────────────
        Schema::create('pelanggaran_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('judul');
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->unsignedInteger('point')->default(0);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['guru_id', 'tanggal']);
            $table->index(['sekolah_id', 'tanggal']);
        });

        // ── Bukti Catatan (proof files — polymorphic) ───────────
        Schema::create('bukti_catatan', function (Blueprint $table) {
            $table->id();
            $table->string('buktiable_type', 100);
            $table->unsignedBigInteger('buktiable_id');
            $table->string('file_path', 500);
            $table->string('file_type', 20);
            $table->string('original_name', 255);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['buktiable_type', 'buktiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_catatan');
        Schema::dropIfExists('pelanggaran_guru');
        Schema::dropIfExists('prestasi_guru');
        Schema::dropIfExists('pelanggaran_siswa');
        Schema::dropIfExists('prestasi_siswa');
    }
};
