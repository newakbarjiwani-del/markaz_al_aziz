<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('potongan_siswa_jenis_tagihan')) {
            return;
        }

        Schema::table('potongan_siswa_jenis_tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('potongan_siswa_jenis_tagihan', 'tipe')) {
                $table->string('tipe', 10)->nullable()->after('jenis_tagihan_id')->comment('percent|fixed');
            }
            if (! Schema::hasColumn('potongan_siswa_jenis_tagihan', 'nilai')) {
                $table->unsignedInteger('nilai')->nullable()->after('tipe');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('potongan_siswa_jenis_tagihan')) {
            return;
        }

        Schema::table('potongan_siswa_jenis_tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('potongan_siswa_jenis_tagihan', 'nilai')) {
                $table->dropColumn('nilai');
            }
            if (Schema::hasColumn('potongan_siswa_jenis_tagihan', 'tipe')) {
                $table->dropColumn('tipe');
            }
        });
    }
};
