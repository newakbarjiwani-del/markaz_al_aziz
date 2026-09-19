<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfidz_surah', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name_ar');
            $table->string('name_id');
            $table->unsignedSmallInteger('ayah_count');
            $table->string('revelation_type', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('tahfidz_ayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('tahfidz_surah')->cascadeOnDelete();
            $table->unsignedSmallInteger('ayah_number');
            $table->text('text_ar');
            $table->text('text_id')->nullable();
            $table->unsignedTinyInteger('juz');
            $table->unsignedSmallInteger('page');
            $table->timestamps();

            $table->unique(['surah_id', 'ayah_number']);
            $table->index(['juz', 'ayah_number']);
            $table->index(['page', 'surah_id']);
        });

        Schema::create('tahfidz_target', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('range_type', 20); // juz | ayat
            $table->unsignedTinyInteger('juz')->nullable();
            $table->foreignId('surah_id')->nullable()->constrained('tahfidz_surah')->nullOnDelete();
            $table->unsignedSmallInteger('ayah_from')->nullable();
            $table->unsignedSmallInteger('ayah_to')->nullable();
            $table->string('period', 20); // daily | weekly
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siswa_id', 'due_date']);
            $table->index(['sekolah_id', 'due_date']);
        });

        Schema::create('tahfidz_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('surah_id')->constrained('tahfidz_surah')->cascadeOnDelete();
            $table->unsignedSmallInteger('ayah_from');
            $table->unsignedSmallInteger('ayah_to');
            $table->string('status', 20)->default('belum');
            $table->timestamp('last_reviewed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siswa_id', 'status']);
            $table->index(['sekolah_id', 'status']);
            $table->index(['surah_id', 'ayah_from', 'ayah_to']);
        });

        Schema::create('tahfidz_murajaah_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('progress_id')->constrained('tahfidz_progress')->cascadeOnDelete();
            $table->timestamp('reviewed_at');
            $table->text('note')->nullable();
            $table->string('source', 20); // siswa | guru
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['progress_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfidz_murajaah_log');
        Schema::dropIfExists('tahfidz_progress');
        Schema::dropIfExists('tahfidz_target');
        Schema::dropIfExists('tahfidz_ayat');
        Schema::dropIfExists('tahfidz_surah');
    }
};
