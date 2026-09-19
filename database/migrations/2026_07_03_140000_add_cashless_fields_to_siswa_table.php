<?php

use App\Models\Siswa;
use App\Support\RfidUid;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->boolean('rfid_blocked')->default(false)->after('rfid_uid');
            $table->decimal('daily_transaction_limit', 15, 2)->nullable()->after('rfid_blocked');
        });

        Siswa::withTrashed()
            ->whereNull('rfid_uid')
            ->whereNotNull('nis')
            ->orderBy('id')
            ->each(function (Siswa $siswa): void {
                $siswa->forceFill([
                    'rfid_uid' => RfidUid::fromNis($siswa->nis, (int) $siswa->sekolah_id),
                ])->saveQuietly();
            });

        Schema::table('siswa', function (Blueprint $table) {
            $table->unique(['rfid_uid', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropUnique(['rfid_uid', 'deleted_at']);
            $table->dropColumn(['rfid_blocked', 'daily_transaction_limit']);
        });
    }
};
