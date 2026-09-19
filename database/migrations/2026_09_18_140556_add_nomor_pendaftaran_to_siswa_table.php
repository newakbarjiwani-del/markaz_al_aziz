<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('nomor_pendaftaran')->nullable()->after('nis_key');
            $table->unique('nomor_pendaftaran');
        });

        if (! Schema::hasTable('profil_siswa')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        $rows = DB::table('profil_siswa')
            ->select(['siswa_id', 'extra_fields'])
            ->whereNotNull('extra_fields')
            ->get();

        foreach ($rows as $row) {
            $extra = is_string($row->extra_fields)
                ? json_decode($row->extra_fields, true)
                : (array) $row->extra_fields;

            $nomor = trim((string) ($extra['nomor_pendaftaran'] ?? ''));
            if ($nomor === '') {
                continue;
            }

            $updated = DB::table('siswa')
                ->where('id', $row->siswa_id)
                ->whereNull('nomor_pendaftaran')
                ->update(['nomor_pendaftaran' => $nomor]);

            if ($updated === 0) {
                continue;
            }

            unset($extra['nomor_pendaftaran']);

            $encoded = $extra === [] ? null : json_encode($extra);

            if ($driver === 'sqlite') {
                DB::table('profil_siswa')
                    ->where('siswa_id', $row->siswa_id)
                    ->update(['extra_fields' => $encoded]);
            } else {
                DB::table('profil_siswa')
                    ->where('siswa_id', $row->siswa_id)
                    ->update(['extra_fields' => $encoded]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('siswa')
            ->select(['id', 'nomor_pendaftaran'])
            ->whereNotNull('nomor_pendaftaran')
            ->get();

        foreach ($rows as $row) {
            $profil = DB::table('profil_siswa')->where('siswa_id', $row->id)->first();
            if ($profil === null) {
                continue;
            }

            $extra = is_string($profil->extra_fields)
                ? json_decode($profil->extra_fields, true)
                : (array) ($profil->extra_fields ?? []);

            $extra['nomor_pendaftaran'] = $row->nomor_pendaftaran;

            DB::table('profil_siswa')
                ->where('siswa_id', $row->id)
                ->update(['extra_fields' => json_encode($extra)]);
        }

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique(['nomor_pendaftaran']);
            $table->dropColumn('nomor_pendaftaran');
        });
    }
};
