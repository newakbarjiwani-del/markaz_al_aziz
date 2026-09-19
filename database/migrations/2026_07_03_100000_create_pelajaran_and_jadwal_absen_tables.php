<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelajaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('code', 20)->nullable();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sekolah_id', 'name', 'deleted_at']);
        });

        Schema::create('jadwal_absen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('assignment_type', 20)->default('kelas');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jadwal_absen_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_absen_id')->constrained('jadwal_absen')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['jadwal_absen_id', 'kelas_id']);
        });

        Schema::create('jadwal_absen_hari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_absen_id')->constrained('jadwal_absen')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jadwal_absen_id', 'day_of_week', 'deleted_at']);
        });

        Schema::create('jadwal_absen_slot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_absen_hari_id')->constrained('jadwal_absen_hari')->cascadeOnDelete();
            $table->foreignId('pelajaran_id')->constrained('pelajaran')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->time('time_start');
            $table->time('time_end');
            $table->unsignedSmallInteger('tolerance_minutes')->default(15);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_absen_slot');
        Schema::dropIfExists('jadwal_absen_hari');
        Schema::dropIfExists('jadwal_absen_kelas');
        Schema::dropIfExists('jadwal_absen');
        Schema::dropIfExists('pelajaran');
    }
};
