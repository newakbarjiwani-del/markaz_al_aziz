<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('hadir');
            $table->time('time_in')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['siswa_id', 'date', 'deleted_at']);
        });

        Schema::create('absensi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('hadir');
            $table->time('time_in')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['guru_id', 'date', 'deleted_at']);
        });

        Schema::create('absensi_qr', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->morphs('checkinable');
            $table->timestamp('checked_in_at');
            $table->string('source')->default('qr');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_qr');
        Schema::dropIfExists('absensi_guru');
        Schema::dropIfExists('absensi_siswa');
    }
};
