<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureIndex('absensi_siswa', 'absensi_siswa_siswa_id_index', 'siswa_id');

        Schema::table('absensi_siswa', function (Blueprint $table) {
            if ($this->indexExists('absensi_siswa', 'absensi_siswa_siswa_id_date_deleted_at_unique')) {
                $table->dropUnique('absensi_siswa_siswa_id_date_deleted_at_unique');
            }
            if (! $this->indexExists('absensi_siswa', 'absensi_siswa_slot_day_unique')) {
                $table->unique(['siswa_id', 'jadwal_absen_slot_id', 'date', 'deleted_at'], 'absensi_siswa_slot_day_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->ensureIndex('absensi_siswa', 'absensi_siswa_siswa_id_index', 'siswa_id');

        Schema::table('absensi_siswa', function (Blueprint $table) {
            if ($this->indexExists('absensi_siswa', 'absensi_siswa_slot_day_unique')) {
                $table->dropUnique('absensi_siswa_slot_day_unique');
            }
            if (! $this->indexExists('absensi_siswa', 'absensi_siswa_siswa_id_date_deleted_at_unique')) {
                $table->unique(['siswa_id', 'date', 'deleted_at']);
            }
        });
    }

    private function ensureIndex(string $table, string $indexName, string $column): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $indexName): void {
            $tableBlueprint->index($column, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = DB::getDatabaseName();

            return DB::table('information_schema.statistics')
                ->where('table_schema', $database)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();
        }

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
