<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->string('name');
            $table->string('nis', 50)->nullable();
            $table->string('angkatan', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sekolah_id', 'angkatan']);
            $table->index(['nis']);
        });

        Schema::create('alumni_tracer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->string('tahun_tracer', 10);
            $table->string('status_lulusan', 30);
            $table->string('institusi')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('bidang')->nullable();
            $table->string('kota')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('source', 20)->default('admin');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['alumni_id', 'tahun_tracer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_tracer');
        Schema::dropIfExists('alumni');
    }
};
