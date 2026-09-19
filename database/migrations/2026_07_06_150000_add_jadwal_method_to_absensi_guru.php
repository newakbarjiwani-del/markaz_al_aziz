<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->foreignId('jadwal_absensi_guru_id')
                ->nullable()
                ->after('guru_id')
                ->constrained('jadwal_absensi_guru')
                ->nullOnDelete();
            $table->string('method', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_absensi_guru_id');
            $table->dropColumn('method');
        });
    }
};
