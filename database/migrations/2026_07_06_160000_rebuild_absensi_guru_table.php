<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('absensi_guru')->truncate();

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->dropColumn('time_in');
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->string('status_pulang', 20)->nullable()->after('status');
            $table->time('jam_masuk')->nullable()->after('method');
            $table->time('jam_keluar')->nullable()->after('jam_masuk');
            $table->string('method_keluar', 20)->nullable()->after('jam_keluar');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->dropColumn(['status_pulang', 'jam_masuk', 'jam_keluar', 'method_keluar']);
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->time('time_in')->nullable()->after('status');
        });
    }
};
