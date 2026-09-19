<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Widen siswa.address and profil_guru.address from varchar(255) to TEXT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'address')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->text('address')->nullable()->change();
            });
        }

        if (Schema::hasTable('profil_guru') && Schema::hasColumn('profil_guru', 'address')) {
            Schema::table('profil_guru', function (Blueprint $table) {
                $table->text('address')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'address')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('address')->nullable()->change();
            });
        }

        if (Schema::hasTable('profil_guru') && Schema::hasColumn('profil_guru', 'address')) {
            Schema::table('profil_guru', function (Blueprint $table) {
                $table->string('address')->nullable()->change();
            });
        }
    }
};
