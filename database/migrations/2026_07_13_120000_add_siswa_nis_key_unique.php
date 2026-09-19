<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MySQL UNIQUE(nis, deleted_at) does not enforce one active NIS
 * (NULL deleted_at values are treated as distinct). Add nis_key for real uniqueness.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('siswa')) {
            return;
        }

        $this->softDeleteDuplicateActiveNis();

        if (Schema::hasColumn('siswa', 'nis_key')) {
            $this->backfillNisKey();

            return;
        }

        Schema::table('siswa', function (Blueprint $table) {
            try {
                $table->dropUnique('siswa_nis_deleted_at_unique');
            } catch (\Throwable) {
                // Index may already be absent on some environments.
            }
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nis_key')->nullable()->after('nis');
        });

        $this->backfillNisKey();

        Schema::table('siswa', function (Blueprint $table) {
            $table->unique('nis_key');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('siswa') || ! Schema::hasColumn('siswa', 'nis_key')) {
            return;
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique(['nis_key']);
            $table->dropColumn('nis_key');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unique(['nis', 'deleted_at']);
        });
    }

    private function softDeleteDuplicateActiveNis(): void
    {
        $duplicates = DB::table('siswa')
            ->select('nis')
            ->whereNull('deleted_at')
            ->groupBy('nis')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nis');

        $now = now();

        foreach ($duplicates as $nis) {
            $ids = DB::table('siswa')
                ->where('nis', $nis)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->pluck('id');

            $ids->shift();

            if ($ids->isEmpty()) {
                continue;
            }

            DB::table('siswa')
                ->whereIn('id', $ids->all())
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    private function backfillNisKey(): void
    {
        DB::table('siswa')
            ->whereNull('deleted_at')
            ->whereNotNull('nis')
            ->update([
                'nis_key' => DB::raw('nis'),
            ]);

        DB::table('siswa')
            ->whereNotNull('deleted_at')
            ->update([
                'nis_key' => null,
            ]);
    }
};
