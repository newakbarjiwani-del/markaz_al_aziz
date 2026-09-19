<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sm_topup') || ! Schema::hasColumn('sm_topup', 'TOPUPNO')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE sm_topup MODIFY TOPUPNO CHAR(16) NULL DEFAULT 'CASHLESS'");

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite cannot reliably alter column defaults; model default covers inserts.
            return;
        }

        DB::statement("ALTER TABLE sm_topup ALTER COLUMN \"TOPUPNO\" SET DEFAULT 'CASHLESS'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('sm_topup') || ! Schema::hasColumn('sm_topup', 'TOPUPNO')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE sm_topup MODIFY TOPUPNO CHAR(16) NULL DEFAULT NULL');

            return;
        }

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE sm_topup ALTER COLUMN "TOPUPNO" DROP DEFAULT');
    }
};
