<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->renameColumn('paid_at', 'paid_dt');
            $table->timestamp('paid_dt_actual')->nullable()->after('paid_dt');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->renameColumn('paid_at', 'paid_dt');
            $table->timestamp('paid_dt_actual')->nullable()->after('paid_dt');
        });

        DB::statement('UPDATE tagihan SET paid_dt_actual = paid_dt WHERE paid_dt IS NOT NULL');
        DB::statement('UPDATE pembayaran SET paid_dt_actual = paid_dt WHERE paid_dt IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropColumn('paid_dt_actual');
            $table->renameColumn('paid_dt', 'paid_at');
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('paid_dt_actual');
            $table->renameColumn('paid_dt', 'paid_at');
        });
    }
};
