<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru', function (Blueprint $table) {
            $table->string('rfid_uid', 64)->nullable()->after('phone');
            $table->unique(['rfid_uid', 'deleted_at']);
        });

        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->foreignId('guru_id')->nullable()->after('siswa_id')->constrained('guru')->nullOnDelete();
            $table->string('rfid_uid', 64)->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guru_id');
            $table->dropColumn('rfid_uid');
        });

        Schema::table('guru', function (Blueprint $table) {
            $table->dropUnique(['rfid_uid', 'deleted_at']);
            $table->dropColumn('rfid_uid');
        });
    }
};
