<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qris_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('qris_payments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('qris_payment_tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('qris_payment_tagihan', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('qris_payments', function (Blueprint $table) {
            if (Schema::hasColumn('qris_payments', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('qris_payment_tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('qris_payment_tagihan', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
