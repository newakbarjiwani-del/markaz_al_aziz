<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dedupeJenisTagihanByName();

        foreach (['tahun_akademik', 'jenis_tagihan'] as $table) {
            if ($this->hasForeignKey($table, "{$table}_sekolah_id_foreign")) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['sekolah_id']);
                });
            }
        }

        if ($this->hasIndex('jenis_tagihan', 'jenis_tagihan_sekolah_id_name_deleted_at_unique')) {
            Schema::table('jenis_tagihan', function (Blueprint $table) {
                $table->dropUnique(['sekolah_id', 'name', 'deleted_at']);
            });
        }

        foreach (['tahun_akademik', 'jenis_tagihan'] as $table) {
            if (! $this->columnIsNullable($table, 'sekolah_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('sekolah_id')->nullable()->change();
                });
            }

            if (! $this->hasForeignKey($table, "{$table}_sekolah_id_foreign")) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreign('sekolah_id')->references('id')->on('sekolah')->nullOnDelete();
                });
            }
        }

        DB::table('tahun_akademik')->whereNotNull('sekolah_id')->update(['sekolah_id' => null]);
        DB::table('jenis_tagihan')->whereNotNull('sekolah_id')->update(['sekolah_id' => null]);

        if (! $this->hasIndex('jenis_tagihan', 'jenis_tagihan_name_deleted_at_unique')) {
            Schema::table('jenis_tagihan', function (Blueprint $table) {
                $table->unique(['name', 'deleted_at']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('jenis_tagihan', 'jenis_tagihan_name_deleted_at_unique')) {
            Schema::table('jenis_tagihan', function (Blueprint $table) {
                $table->dropUnique(['name', 'deleted_at']);
            });
        }

        $fallbackSekolahId = DB::table('sekolah')->orderBy('id')->value('id') ?? 1;

        DB::table('tahun_akademik')->whereNull('sekolah_id')->update(['sekolah_id' => $fallbackSekolahId]);
        DB::table('jenis_tagihan')->whereNull('sekolah_id')->update(['sekolah_id' => $fallbackSekolahId]);

        foreach (['tahun_akademik', 'jenis_tagihan'] as $table) {
            if ($this->hasForeignKey($table, "{$table}_sekolah_id_foreign")) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['sekolah_id']);
                });
            }

            if ($this->columnIsNullable($table, 'sekolah_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
                });
            }

            if (! $this->hasForeignKey($table, "{$table}_sekolah_id_foreign")) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreign('sekolah_id')->references('id')->on('sekolah')->cascadeOnDelete();
                });
            }
        }

        if (! $this->hasIndex('jenis_tagihan', 'jenis_tagihan_sekolah_id_name_deleted_at_unique')) {
            Schema::table('jenis_tagihan', function (Blueprint $table) {
                $table->unique(['sekolah_id', 'name', 'deleted_at']);
            });
        }
    }

    private function dedupeJenisTagihanByName(): void
    {
        $duplicateNames = DB::table('jenis_tagihan')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $name) {
            $ids = DB::table('jenis_tagihan')
                ->where('name', $name)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->pluck('id');

            $keepId = $ids->first();

            foreach ($ids->slice(1) as $removeId) {
                DB::table('tagihan')
                    ->where('jenis_tagihan_id', $removeId)
                    ->update(['jenis_tagihan_id' => $keepId]);

                DB::table('jenis_tagihan')->where('id', $removeId)->delete();
            }
        }
    }

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('{$table}')") as $foreignKeyRow) {
                if ($foreignKeyRow->from === 'sekolah_id') {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA index_list('{$table}')") as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA table_info('{$table}')") as $columnInfo) {
                if ($columnInfo->name === $column) {
                    return (int) $columnInfo->notnull === 0;
                }
            }

            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();

        return DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->value('IS_NULLABLE') === 'YES';
    }
};
