<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['siswa_id', 'deleted_at'], 'users_siswa_id_deleted_at_unique');
            $table->unique(['guru_id', 'deleted_at'], 'users_guru_id_deleted_at_unique');
            $table->unique(['orang_tua_id', 'deleted_at'], 'users_orang_tua_id_deleted_at_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_siswa_id_deleted_at_unique');
            $table->dropUnique('users_guru_id_deleted_at_unique');
            $table->dropUnique('users_orang_tua_id_deleted_at_unique');
        });
    }
};
