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
            if (! Schema::hasColumn('potongan_siswa_jenis_tagihan', 'max_pemakaian')) {
                $table->unsignedSmallInteger('max_pemakaian')->nullable()->after('nilai');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('potongan_siswa_jenis_tagihan')) {
            return;
        }

        Schema::table('potongan_siswa_jenis_tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('potongan_siswa_jenis_tagihan', 'max_pemakaian')) {
                $table->dropColumn('max_pemakaian');
            }
        });
    }
};
