<?php

use App\Support\SiswaStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert siswa.status from varchar (aktif/nonaktif/pending) to unsignedTinyInteger.
 *
 * 0 = nonaktif, 1 = aktif (default), 2 = pending (reserved for admission).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('siswa')) {
            return;
        }

        if (! Schema::hasColumn('siswa', 'status')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->unsignedTinyInteger('status')->default(SiswaStatus::ACTIVE)->after('address');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $type = $this->statusColumnType($driver);

        if (in_array($type, ['tinyint', 'smallint', 'integer', 'int', 'bigint'], true)) {
            // Already numeric (e.g. fresh install after create migration update).
            $this->normalizeNumericCodes();

            return;
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->unsignedTinyInteger('status_code')->default(SiswaStatus::ACTIVE)->after('address');
        });

        foreach (SiswaStatus::legacyMap() as $legacy => $code) {
            DB::table('siswa')->where('status', $legacy)->update(['status_code' => $code]);
        }

        // Numeric strings already stored as "0"/"1"/"2"
        foreach (SiswaStatus::values() as $code) {
            DB::table('siswa')->where('status', (string) $code)->update(['status_code' => $code]);
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->renameColumn('status_code', 'status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('siswa') || ! Schema::hasColumn('siswa', 'status')) {
            return;
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('status_legacy')->default('aktif')->after('address');
        });

        $reverse = array_flip(SiswaStatus::legacyMap());
        foreach ($reverse as $code => $label) {
            DB::table('siswa')->where('status', $code)->update(['status_legacy' => $label]);
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->renameColumn('status_legacy', 'status');
        });
    }

    private function statusColumnType(string $driver): ?string
    {
        if ($driver === 'sqlite') {
            $row = DB::selectOne("SELECT typeof(status) AS t FROM siswa LIMIT 1");
            if ($row && isset($row->t) && in_array(strtolower((string) $row->t), ['integer', 'real'], true)) {
                return 'integer';
            }

            // Empty table or text — inspect pragma
            $cols = DB::select('PRAGMA table_info(siswa)');
            foreach ($cols as $col) {
                if (($col->name ?? null) === 'status') {
                    return strtolower((string) ($col->type ?? ''));
                }
            }

            return null;
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'SELECT DATA_TYPE AS t FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['siswa', 'status']
            );

            return isset($row->t) ? strtolower((string) $row->t) : null;
        }

        return null;
    }

    private function normalizeNumericCodes(): void
    {
        // Clamp unknown codes to ACTIVE for safety on already-numeric columns.
        DB::table('siswa')
            ->whereNotIn('status', SiswaStatus::values())
            ->update(['status' => SiswaStatus::ACTIVE]);
    }
};
