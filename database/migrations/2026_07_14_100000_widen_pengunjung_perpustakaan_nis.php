<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pengunjung_perpustakaan') || ! Schema::hasColumn('pengunjung_perpustakaan', 'nis')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE pengunjung_perpustakaan MODIFY nis VARCHAR(32) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pengunjung_perpustakaan ALTER COLUMN nis TYPE VARCHAR(32)');

            return;
        }

        // SQLite ignores VARCHAR length; no-op for tests.
    }

    public function down(): void
    {
        if (! Schema::hasTable('pengunjung_perpustakaan') || ! Schema::hasColumn('pengunjung_perpustakaan', 'nis')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE pengunjung_perpustakaan MODIFY nis VARCHAR(20) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE pengunjung_perpustakaan ALTER COLUMN nis TYPE VARCHAR(20)');
        }
    }
};
