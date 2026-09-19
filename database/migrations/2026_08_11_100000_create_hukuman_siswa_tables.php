<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hukuman_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->unsignedInteger('total_point')->default(0);
            $table->string('recommended_sanction', 50)->nullable();
            $table->string('sanction', 50)->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->text('keterangan')->nullable();
            $table->date('tanggal');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['siswa_id', 'tanggal']);
            $table->index(['sekolah_id', 'status']);
        });

        Schema::create('hukuman_pelanggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hukuman_siswa_id')->constrained('hukuman_siswa')->cascadeOnDelete();
            $table->foreignId('pelanggaran_siswa_id')->constrained('pelanggaran_siswa')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hukuman_siswa_id', 'pelanggaran_siswa_id'], 'hukuman_pelanggaran_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hukuman_pelanggaran');
        Schema::dropIfExists('hukuman_siswa');
    }
};
