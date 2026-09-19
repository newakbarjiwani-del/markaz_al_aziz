<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            if (! Schema::hasColumn('kelas', 'kelas')) {
                $table->string('kelas', 50)->nullable()->after('name');
            }

            if (! Schema::hasColumn('kelas', 'kelompok')) {
                $table->string('kelompok', 50)->nullable()->after('kelas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            if (Schema::hasColumn('kelas', 'kelompok')) {
                $table->dropColumn('kelompok');
            }

            if (Schema::hasColumn('kelas', 'kelas')) {
                $table->dropColumn('kelas');
            }
        });
    }
};
