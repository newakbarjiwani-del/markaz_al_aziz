<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('nip');
            $table->string('name');
            $table->string('jabatan')->nullable();
            $table->string('jenis_guru')->nullable();
            $table->string('golongan')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['nip', 'deleted_at']);
        });

        Schema::create('profil_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->string('photo_path')->nullable();
            $table->text('address')->nullable();
            $table->json('extra_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dokumen_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kartu_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->string('status')->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('riwayat_mengajar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->string('class_name')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('guru_id')->references('id')->on('guru')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
        });

        Schema::dropIfExists('riwayat_mengajar');
        Schema::dropIfExists('kartu_guru');
        Schema::dropIfExists('dokumen_guru');
        Schema::dropIfExists('profil_guru');
        Schema::dropIfExists('guru');
    }
};
