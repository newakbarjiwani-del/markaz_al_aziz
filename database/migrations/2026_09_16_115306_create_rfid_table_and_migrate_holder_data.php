<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 64)->unique();
            $table->foreignId('siswa_id')->nullable()->unique()->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('guru_id')->nullable()->unique()->constrained('guru')->cascadeOnDelete();
            $table->boolean('blocked')->default(false);
            $table->timestamps();
        });

        $crossHolderCollisions = DB::table('siswa')
            ->join('guru', 'guru.rfid_uid', '=', 'siswa.rfid_uid')
            ->whereNull('siswa.deleted_at')
            ->whereNull('guru.deleted_at')
            ->whereNotNull('siswa.rfid_uid')
            ->where('siswa.rfid_uid', '!=', '')
            ->select(['siswa.rfid_uid as uid', 'siswa.id as siswa_id', 'guru.id as guru_id'])
            ->limit(10)
            ->get();

        $duplicateSiswaUids = DB::table('siswa')
            ->whereNull('deleted_at')
            ->whereNotNull('rfid_uid')
            ->where('rfid_uid', '!=', '')
            ->groupBy('rfid_uid')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->pluck('rfid_uid');

        $duplicateGuruUids = DB::table('guru')
            ->whereNull('deleted_at')
            ->whereNotNull('rfid_uid')
            ->where('rfid_uid', '!=', '')
            ->groupBy('rfid_uid')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->pluck('rfid_uid');

        $collisionUids = $crossHolderCollisions->pluck('uid')
            ->concat($duplicateSiswaUids)
            ->concat($duplicateGuruUids)
            ->unique()
            ->values();

        if ($collisionUids->isNotEmpty()) {
            Schema::dropIfExists('rfid');

            throw new RuntimeException(
                'Migrasi RFID dibatalkan: UID aktif digunakan lebih dari satu pemilik: '.
                $collisionUids->implode(', ')
            );
        }

        $now = now();

        DB::table('siswa')
            ->whereNull('deleted_at')
            ->whereNotNull('rfid_uid')
            ->where('rfid_uid', '!=', '')
            ->select(['id', 'rfid_uid', 'rfid_blocked'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now): void {
                DB::table('rfid')->insert(
                    $rows->map(fn ($row) => [
                        'uid' => $row->rfid_uid,
                        'siswa_id' => $row->id,
                        'guru_id' => null,
                        'blocked' => (bool) $row->rfid_blocked,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        DB::table('guru')
            ->whereNull('deleted_at')
            ->whereNotNull('rfid_uid')
            ->where('rfid_uid', '!=', '')
            ->select(['id', 'rfid_uid'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now): void {
                DB::table('rfid')->insert(
                    $rows->map(fn ($row) => [
                        'uid' => $row->rfid_uid,
                        'siswa_id' => null,
                        'guru_id' => $row->id,
                        'blocked' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique('siswa_rfid_uid_deleted_at_unique');
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->dropUnique('guru_rfid_uid_deleted_at_unique');
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['rfid_uid', 'rfid_blocked']);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->dropColumn('rfid_uid');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('rfid_uid', 64)->nullable();
            $table->boolean('rfid_blocked')->default(false);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->string('rfid_uid', 64)->nullable();
        });

        DB::table('rfid')->whereNotNull('siswa_id')->orderBy('id')->each(function ($row): void {
            DB::table('siswa')->where('id', $row->siswa_id)->update([
                'rfid_uid' => $row->uid,
                'rfid_blocked' => $row->blocked,
            ]);
        });

        DB::table('rfid')->whereNotNull('guru_id')->orderBy('id')->each(function ($row): void {
            DB::table('guru')->where('id', $row->guru_id)->update([
                'rfid_uid' => $row->uid,
            ]);
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unique(['rfid_uid', 'deleted_at']);
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->unique(['rfid_uid', 'deleted_at']);
        });

        Schema::dropIfExists('rfid');
    }
};
