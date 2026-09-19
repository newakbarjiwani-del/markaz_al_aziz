<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->string('borrower_type', 20)->default('siswa')->after('buku_id');
            $table->foreignId('guru_id')->nullable()->after('siswa_id')->constrained('guru')->nullOnDelete();
            $table->string('tamu_nama')->nullable()->after('guru_id');
            $table->string('tamu_asal')->nullable()->after('tamu_nama');
            $table->string('tamu_telepon', 30)->nullable()->after('tamu_asal');
        });

        // Make siswa_id nullable (SQLite-friendly: drop FK, change, re-add).
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
        });

        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->unsignedBigInteger('siswa_id')->nullable()->change();
        });

        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->foreign('siswa_id')->references('id')->on('siswa')->nullOnDelete();
        });

        DB::table('peminjaman_buku')->where('borrower_type', '')->update(['borrower_type' => 'siswa']);
    }

    public function down(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->dropColumn(['borrower_type', 'guru_id', 'tamu_nama', 'tamu_asal', 'tamu_telepon']);
        });

        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
        });

        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->unsignedBigInteger('siswa_id')->nullable(false)->change();
        });

        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->foreign('siswa_id')->references('id')->on('siswa')->cascadeOnDelete();
        });
    }
};
