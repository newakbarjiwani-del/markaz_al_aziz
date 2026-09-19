<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_sesi_pengecualian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_absen_slot_id')->constrained('jadwal_absen_slot')->cascadeOnDelete();
            $table->date('date');
            $table->string('reason');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['jadwal_absen_slot_id', 'date', 'deleted_at'], 'absensi_sesi_pengecualian_slot_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_sesi_pengecualian');
    }
};
