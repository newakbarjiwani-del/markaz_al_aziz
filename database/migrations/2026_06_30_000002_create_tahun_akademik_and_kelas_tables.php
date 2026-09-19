<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('name');
            $table->string('kelas', 50)->nullable();
            $table->string('kelompok', 50)->nullable();
            $table->string('unit')->nullable();
            $table->string('jenjang')->nullable();
            $table->string('wali_kelas')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('tahun_akademik');
    }
};
