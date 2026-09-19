<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perizinan', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('catatan')->comment('Path file bukti perizinan di public disk');
        });
    }

    public function down(): void
    {
        Schema::table('perizinan', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
