<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_absen', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('jadwal_absen', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable()->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_absen', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('jadwal_absen', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->cascadeOnDelete();
        });
    }
};
