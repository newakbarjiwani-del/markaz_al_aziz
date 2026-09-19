<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sccttran') && ! Schema::hasIndex('sccttran', 'sccttran_custid_trxdate_index')) {
            Schema::table('sccttran', function (Blueprint $table) {
                $table->index(['CUSTID', 'TRXDATE'], 'sccttran_custid_trxdate_index');
            });
        }

        if (Schema::hasTable('sccttran_cashless')) {
            Schema::table('sccttran_cashless', function (Blueprint $table) {
                if (! Schema::hasIndex('sccttran_cashless', 'sccttran_cashless_custid_trxdate_index')) {
                    $table->index(['CUSTID', 'TRXDATE'], 'sccttran_cashless_custid_trxdate_index');
                }

                if (! Schema::hasIndex('sccttran_cashless', 'sccttran_cashless_custid_wallet_index')) {
                    $table->index(['CUSTID', 'wallet'], 'sccttran_cashless_custid_wallet_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sccttran') && Schema::hasIndex('sccttran', 'sccttran_custid_trxdate_index')) {
            Schema::table('sccttran', function (Blueprint $table) {
                $table->dropIndex('sccttran_custid_trxdate_index');
            });
        }

        if (Schema::hasTable('sccttran_cashless')) {
            Schema::table('sccttran_cashless', function (Blueprint $table) {
                if (Schema::hasIndex('sccttran_cashless', 'sccttran_cashless_custid_trxdate_index')) {
                    $table->dropIndex('sccttran_cashless_custid_trxdate_index');
                }

                if (Schema::hasIndex('sccttran_cashless', 'sccttran_cashless_custid_wallet_index')) {
                    $table->dropIndex('sccttran_cashless_custid_wallet_index');
                }
            });
        }
    }
};
