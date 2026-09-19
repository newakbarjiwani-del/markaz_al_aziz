<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orang_tua', function (Blueprint $table) {
            $table->string('nama_wali')->nullable()->after('pekerjaan_ibu');
            $table->string('telepon_wali')->nullable()->after('nama_wali');
            $table->string('email_wali')->nullable()->after('telepon_wali');
            $table->string('pekerjaan_wali')->nullable()->after('email_wali');
        });
    }

    public function down(): void
    {
        Schema::table('orang_tua', function (Blueprint $table) {
            $table->dropColumn(['nama_wali', 'telepon_wali', 'email_wali', 'pekerjaan_wali']);
        });
    }
};
