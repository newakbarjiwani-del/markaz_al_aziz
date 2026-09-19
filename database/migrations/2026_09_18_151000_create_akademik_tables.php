<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->string('kelompok')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kurikulum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('name');
            $table->string('jenjang')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kurikulum_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_id')->constrained('kurikulum')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('tingkat')->nullable();
            $table->unsignedTinyInteger('jam_mingguan')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['kurikulum_id', 'mata_pelajaran_id', 'tingkat'], 'kurikulum_mapel_unique');
        });

        Schema::create('kompetensi_dasar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kurikulum_mapel_id')->constrained('kurikulum_mapel')->cascadeOnDelete();
            $table->string('kode', 50);
            $table->text('deskripsi');
            $table->string('semester', 10)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jadwal_pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jadwal_pelajaran_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_pelajaran_id')->constrained('jadwal_pelajaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('time_start');
            $table->time('time_end');
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('guru')->nullOnDelete();
            $table->string('ruang')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kalender_pendidikan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('jenis', 20)->default('efektif');
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nilai_entry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('semester', 10);
            $table->string('jenis', 20)->default('harian');
            $table->foreignId('kompetensi_dasar_id')->nullable()->constrained('kompetensi_dasar')->nullOnDelete();
            $table->decimal('skor', 5, 2);
            $table->text('catatan')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rapor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('semester', 10);
            $table->string('status', 20)->default('draft');
            $table->text('catatan_wali')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['siswa_id', 'tahun_akademik_id', 'semester'], 'rapor_siswa_tahun_semester_unique');
        });

        Schema::create('rapor_mapel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapor_id')->constrained('rapor')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->string('predikat', 10)->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['rapor_id', 'mata_pelajaran_id'], 'rapor_mapel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_mapel');
        Schema::dropIfExists('rapor');
        Schema::dropIfExists('nilai_entry');
        Schema::dropIfExists('kalender_pendidikan');
        Schema::dropIfExists('jadwal_pelajaran_slot');
        Schema::dropIfExists('jadwal_pelajaran');
        Schema::dropIfExists('kompetensi_dasar');
        Schema::dropIfExists('kurikulum_mapel');
        Schema::dropIfExists('kurikulum');
        Schema::dropIfExists('mata_pelajaran');
    }
};
