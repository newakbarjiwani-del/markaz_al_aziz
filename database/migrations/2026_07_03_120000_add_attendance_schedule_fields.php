<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('rfid_uid', 64)->nullable()->after('foto_wajah');
        });

        Schema::table('absensi_siswa', function (Blueprint $table) {
            $table->foreignId('jadwal_absen_slot_id')->nullable()->after('siswa_id')->constrained('jadwal_absen_slot')->nullOnDelete();
            $table->string('method', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_siswa', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_absen_slot_id');
            $table->dropColumn('method');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('rfid_uid');
        });
    }
};
