<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengunjung_perpustakaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('visitor_type', 20);
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('nama');
            $table->string('nis', 32)->nullable();
            $table->string('asal')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->longText('foto_wajah')->nullable();
            $table->string('method', 20)->default('manual');
            $table->dateTime('visited_at');
            $table->text('catatan')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sekolah_id', 'visited_at']);
            $table->index(['visitor_type', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengunjung_perpustakaan');
    }
};
