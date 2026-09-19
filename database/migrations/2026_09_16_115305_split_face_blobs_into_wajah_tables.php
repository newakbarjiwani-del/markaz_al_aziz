<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_wajah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->unique()->constrained('siswa')->cascadeOnDelete();
            $table->longText('foto_wajah');
            $table->timestamps();
        });

        Schema::create('pengunjung_perpustakaan_wajah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengunjung_perpustakaan_id')
                ->unique()
                ->constrained('pengunjung_perpustakaan')
                ->cascadeOnDelete();
            $table->longText('foto_wajah');
            $table->timestamps();
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unsignedTinyInteger('has_foto_wajah')->default(0)->index();
        });

        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->unsignedTinyInteger('has_foto_wajah')->default(0)->index();
        });

        $now = now();

        DB::table('siswa')
            ->whereNotNull('foto_wajah')
            ->whereRaw('LENGTH(foto_wajah) > 30')
            ->select(['id', 'foto_wajah'])
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($now): void {
                DB::table('siswa_wajah')->insert(
                    $rows->map(fn ($row) => [
                        'siswa_id' => $row->id,
                        'foto_wajah' => $row->foto_wajah,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );

                DB::table('siswa')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['has_foto_wajah' => 1]);
            });

        DB::table('pengunjung_perpustakaan')
            ->whereNotNull('foto_wajah')
            ->whereRaw('LENGTH(foto_wajah) > 30')
            ->select(['id', 'foto_wajah'])
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($now): void {
                DB::table('pengunjung_perpustakaan_wajah')->insert(
                    $rows->map(fn ($row) => [
                        'pengunjung_perpustakaan_id' => $row->id,
                        'foto_wajah' => $row->foto_wajah,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );

                DB::table('pengunjung_perpustakaan')
                    ->whereIn('id', $rows->pluck('id'))
                    ->update(['has_foto_wajah' => 1]);
            });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('foto_wajah');
        });

        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->dropColumn('foto_wajah');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->longText('foto_wajah')->nullable();
        });

        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->longText('foto_wajah')->nullable();
        });

        DB::table('siswa_wajah')
            ->select(['id', 'siswa_id', 'foto_wajah'])
            ->orderBy('id')
            ->chunkById(50, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('siswa')->where('id', $row->siswa_id)->update([
                        'foto_wajah' => $row->foto_wajah,
                    ]);
                }
            });

        DB::table('pengunjung_perpustakaan_wajah')
            ->select(['id', 'pengunjung_perpustakaan_id', 'foto_wajah'])
            ->orderBy('id')
            ->chunkById(50, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('pengunjung_perpustakaan')
                        ->where('id', $row->pengunjung_perpustakaan_id)
                        ->update(['foto_wajah' => $row->foto_wajah]);
                }
            });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn('has_foto_wajah');
        });

        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->dropColumn('has_foto_wajah');
        });

        Schema::dropIfExists('pengunjung_perpustakaan_wajah');
        Schema::dropIfExists('siswa_wajah');
    }
};
