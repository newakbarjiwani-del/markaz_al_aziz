<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('angkatan');
            $table->string('peserta_label')->default('SANTRIWATI');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sekolah_id', 'is_active']);
        });

        Schema::create('tahfidz_halaqoh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('tahfidz_program')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->restrictOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['program_id', 'guru_id']);
            $table->index(['sekolah_id', 'guru_id']);
        });

        Schema::create('tahfidz_halaqoh_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('halaqoh_id')->constrained('tahfidz_halaqoh')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->unsignedTinyInteger('total_juz')->default(0);
            $table->timestamps();

            $table->unique(['halaqoh_id', 'siswa_id']);
        });

        Schema::create('tahfidz_jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('halaqoh_id')->constrained('tahfidz_halaqoh')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time_start');
            $table->time('time_end');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['halaqoh_id', 'day_of_week', 'is_active']);
        });

        Schema::create('tahfidz_rekap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('tahfidz_program')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['program_id', 'starts_on', 'ends_on']);
            $table->index(['sekolah_id', 'status']);
        });

        Schema::create('tahfidz_rekap_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('tahfidz_rekap')->cascadeOnDelete();
            $table->foreignId('halaqoh_id')->constrained('tahfidz_halaqoh')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->json('tatsbit_juz')->nullable();
            $table->json('murojaah_juz')->nullable();
            $table->json('kehadiran_harian')->nullable();
            $table->unsignedTinyInteger('hadir_hari')->default(0);
            $table->unsignedTinyInteger('sakit_hari')->default(0);
            $table->unsignedTinyInteger('pulang_hari')->default(0);
            $table->unsignedTinyInteger('total_juz')->default(0);
            $table->string('prestasi')->nullable();
            $table->timestamps();

            $table->unique(['rekap_id', 'siswa_id']);
            $table->index(['rekap_id', 'halaqoh_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_rekap_siswa');
        Schema::dropIfExists('tahfidz_rekap');
        Schema::dropIfExists('tahfidz_jadwal');
        Schema::dropIfExists('tahfidz_halaqoh_anggota');
        Schema::dropIfExists('tahfidz_halaqoh');
        Schema::dropIfExists('tahfidz_program');
    }
};
