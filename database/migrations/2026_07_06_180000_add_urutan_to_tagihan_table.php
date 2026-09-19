<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tagihan') && ! Schema::hasColumn('tagihan', 'urutan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->unsignedInteger('urutan')->nullable()->after('periode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tagihan', 'urutan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropColumn('urutan');
            });
        }
    }
};
