<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggaran_siswa', function (Blueprint $table) {
            if (! Schema::hasColumn('pelanggaran_siswa', 'is_punished')) {
                $table->boolean('is_punished')->default(false)->after('point');
                $table->index('is_punished', 'pelanggaran_siswa_punished_idx');
            }
            if (! Schema::hasColumn('pelanggaran_siswa', 'point_asli')) {
                $table->unsignedInteger('point_asli')->nullable()->after('is_punished');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pelanggaran_siswa', function (Blueprint $table) {
            if (Schema::hasColumn('pelanggaran_siswa', 'point_asli')) {
                $table->dropColumn('point_asli');
            }
            if (Schema::hasColumn('pelanggaran_siswa', 'is_punished')) {
                $table->dropIndex('pelanggaran_siswa_punished_idx');
                $table->dropColumn('is_punished');
            }
        });
    }
};
