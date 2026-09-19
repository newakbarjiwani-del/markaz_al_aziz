<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihan', 'paid_at')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->timestamp('paid_at')->nullable()->after('status');
            });
        }

        DB::table('tagihan')
            ->where('status', 1)
            ->whereNull('paid_at')
            ->orderBy('id')
            ->each(function ($row) {
                $paidAt = DB::table('pembayaran')
                    ->where('tagihan_id', $row->id)
                    ->whereNull('deleted_at')
                    ->max('paid_at');

                if ($paidAt) {
                    DB::table('tagihan')->where('id', $row->id)->update(['paid_at' => $paidAt]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('tagihan', 'paid_at')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropColumn('paid_at');
            });
        }
    }
};
