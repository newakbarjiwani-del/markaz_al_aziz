<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sccttran_cashless', function (Blueprint $table) {
            $table->string('wallet', 20)->nullable()->after('METODE');
            $table->index('wallet');
        });
    }

    public function down(): void
    {
        Schema::table('sccttran_cashless', function (Blueprint $table) {
            $table->dropIndex(['wallet']);
            $table->dropColumn('wallet');
        });
    }
};
