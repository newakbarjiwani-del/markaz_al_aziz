<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow orang_tua.sekolah_id to be null (parent can have children in multiple schools).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orang_tua') || ! Schema::hasColumn('orang_tua', 'sekolah_id')) {
            return;
        }

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable()->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orang_tua') || ! Schema::hasColumn('orang_tua', 'sekolah_id')) {
            return;
        }

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->dropForeign(['sekolah_id']);
        });

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
            $table->foreign('sekolah_id')->references('id')->on('sekolah')->cascadeOnDelete();
        });
    }
};
