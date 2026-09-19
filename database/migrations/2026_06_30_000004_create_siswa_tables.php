<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('nama_ayah')->nullable();
            $table->string('telepon_ayah')->nullable();
            $table->string('email_ayah')->nullable();
            $table->string('pekerjaan_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('telepon_ibu')->nullable();
            $table->string('email_ibu')->nullable();
            $table->string('pekerjaan_ibu')->nullable();
            $table->string('alamat')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('nis');
            $table->string('nis_key')->nullable()->unique();
            $table->string('name');
            $table->string('gender', 1)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('address')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orang_tua_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orang_tua_id')->constrained('orang_tua')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['orang_tua_id', 'siswa_id']);
        });

        Schema::create('profil_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('photo_path')->nullable();
            $table->json('extra_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dokumen_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kartu_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('qr_code')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pindah_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('dari_kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->foreignId('ke_kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('riwayat_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->string('class_name')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('siswa_id')->references('id')->on('siswa')->nullOnDelete();
            $table->foreign('orang_tua_id')->references('id')->on('orang_tua')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['siswa_id']);
            $table->dropForeign(['orang_tua_id']);
        });

        Schema::dropIfExists('riwayat_akademik');
        Schema::dropIfExists('pindah_kelas');
        Schema::dropIfExists('kartu_siswa');
        Schema::dropIfExists('dokumen_siswa');
        Schema::dropIfExists('profil_siswa');
        Schema::dropIfExists('orang_tua_siswa');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('orang_tua');
    }
};
