<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('siswa', 'cashless_pin_hash') && ! Schema::hasColumn('siswa', 'cashless_pin')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->renameColumn('cashless_pin_hash', 'cashless_pin');
            });

            return;
        }

        if (! Schema::hasColumn('siswa', 'cashless_pin')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('cashless_pin')->nullable()->after('daily_transaction_limit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswa', 'cashless_pin')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('cashless_pin');
            });
        }
    }
};
