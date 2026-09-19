<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_absensi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('name', 120);
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->unsignedSmallInteger('toleransi_menit')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sekolah_id', 'name', 'deleted_at']);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->foreignId('jadwal_absensi_guru_id')
                ->nullable()
                ->after('sekolah_id')
                ->constrained('jadwal_absensi_guru')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_absensi_guru_id');
        });

        Schema::dropIfExists('jadwal_absensi_guru');
    }
};
