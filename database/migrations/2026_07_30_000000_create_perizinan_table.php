<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perizinan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('jenis_perizinan', 50)->comment('keluar_masuk, keluar_masuk_pondok, pulang_libur');
            $table->text('alasan');
            $table->dateTime('tgl_mulai');
            $table->dateTime('tgl_sampai');
            $table->dateTime('tgl_kembali_aktual')->nullable();
            $table->string('penanggung_jawab')->nullable()->comment('Nama penjemput / wali / penanggung jawab');
            $table->string('status', 30)->default('disetujui')->comment('pending, disetujui, ditolak, kembali, terlambat');
            $table->text('catatan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sekolah_id', 'jenis_perizinan']);
            $table->index(['siswa_id', 'jenis_perizinan']);
            $table->index(['status', 'tgl_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perizinan');
    }
};
