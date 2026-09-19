<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kas_manual', function (Blueprint $table) {
            $table->dropColumn('jenis');
            $table->dropColumn('jumlah');
            $table->unsignedBigInteger('kredit')->nullable()->after('kategori');
            $table->unsignedBigInteger('debet')->nullable()->after('kredit');
        });
    }

    public function down(): void
    {
        Schema::table('kas_manual', function (Blueprint $table) {
            $table->dropColumn('kredit');
            $table->dropColumn('debet');
            $table->enum('jenis', ['masuk', 'keluar'])->after('tanggal');
            $table->unsignedBigInteger('jumlah')->after('deskripsi');
        });
    }
};
