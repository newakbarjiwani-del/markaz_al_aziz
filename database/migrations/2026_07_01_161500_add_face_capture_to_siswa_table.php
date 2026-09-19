<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->longText('foto_wajah')->nullable()->after('status');
        });

        Schema::create('siswa_rekam_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('nis', 32);
            $table->string('jenis', 16);
            $table->string('rfid_uid', 64)->nullable();
            $table->boolean('punya_foto')->default(false);
            $table->string('kode_suara', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_rekam_log');

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('foto_wajah');
        });
    }
};
