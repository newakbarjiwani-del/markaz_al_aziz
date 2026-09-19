<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->unsignedTinyInteger('qty')->default(1)->after('buku_id');
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
