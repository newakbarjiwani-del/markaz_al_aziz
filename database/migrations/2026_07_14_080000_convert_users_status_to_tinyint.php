<?php

use App\Support\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert users.status from varchar (aktif/nonaktif/…) to unsignedTinyInteger.
 *
 * 0 = disabled / blocked, 1 = active (default).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('status')->default(UserStatus::ACTIVE)->after('password');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $type = $this->statusColumnType($driver);

        if (in_array($type, ['tinyint', 'smallint', 'integer', 'int', 'bigint'], true)) {
            $this->normalizeNumericCodes();

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('status_code')->default(UserStatus::ACTIVE)->after('password');
        });

        foreach (UserStatus::legacyMap() as $legacy => $code) {
            DB::table('users')->whereRaw('LOWER(TRIM(status)) = ?', [$legacy])->update(['status_code' => $code]);
        }

        foreach (UserStatus::values() as $code) {
            DB::table('users')->where('status', (string) $code)->update(['status_code' => $code]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('status_code', 'status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'status')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('status_legacy')->default('aktif')->after('password');
        });

        $reverse = [
            UserStatus::DISABLED => 'nonaktif',
            UserStatus::ACTIVE => 'aktif',
        ];

        foreach ($reverse as $code => $label) {
            DB::table('users')->where('status', $code)->update(['status_legacy' => $label]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('status_legacy', 'status');
        });
    }

    private function statusColumnType(string $driver): ?string
    {
        if ($driver === 'sqlite') {
            $row = DB::selectOne('SELECT typeof(status) AS t FROM users LIMIT 1');
            if ($row && isset($row->t) && in_array(strtolower((string) $row->t), ['integer', 'real'], true)) {
                return 'integer';
            }

            $cols = DB::select('PRAGMA table_info(users)');
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
                ['users', 'status']
            );

            return isset($row->t) ? strtolower((string) $row->t) : null;
        }

        return null;
    }

    private function normalizeNumericCodes(): void
    {
        DB::table('users')
            ->whereNotIn('status', UserStatus::values())
            ->update(['status' => UserStatus::ACTIVE]);
    }
};
