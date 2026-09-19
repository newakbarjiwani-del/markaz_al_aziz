<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik')->cascadeOnDelete();
            $table->string('semester', 10);
            $table->foreignId('mata_pelajaran_id')->nullable()->constrained('mata_pelajaran')->nullOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('guru')->nullOnDelete();
            $table->string('title');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('ujian_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('jenis', 20);
            $table->text('pertanyaan');
            $table->decimal('poin', 8, 2)->default(1);
            $table->json('opsi')->nullable();
            $table->string('kunci', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ujian_attempt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('in_progress');
            $table->decimal('skor_mcq', 8, 2)->nullable();
            $table->decimal('skor_max_mcq', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ujian_id', 'siswa_id']);
        });

        Schema::create('ujian_jawaban', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('ujian_attempt')->cascadeOnDelete();
            $table->foreignId('ujian_soal_id')->constrained('ujian_soal')->cascadeOnDelete();
            $table->text('jawaban')->nullable();
            $table->boolean('is_benar')->nullable();
            $table->decimal('poin_didapat', 8, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['attempt_id', 'ujian_soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_jawaban');
        Schema::dropIfExists('ujian_attempt');
        Schema::dropIfExists('ujian_soal');
        Schema::dropIfExists('ujian');
    }
};
