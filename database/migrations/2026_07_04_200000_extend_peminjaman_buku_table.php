<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->text('catatan_pinjam')->nullable()->after('fine_amount');
            $table->text('catatan_kembali')->nullable()->after('catatan_pinjam');
            $table->string('kondisi_kembali')->nullable()->after('catatan_kembali');
            $table->unsignedSmallInteger('late_days')->default(0)->after('kondisi_kembali');
            $table->unsignedTinyInteger('perpanjangan_count')->default(0)->after('late_days');
            $table->foreignId('processed_by_user_id')->nullable()->after('perpanjangan_count')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_buku', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by_user_id');
            $table->dropColumn([
                'catatan_pinjam',
                'catatan_kembali',
                'kondisi_kembali',
                'late_days',
                'perpanjangan_count',
            ]);
        });
    }
};
