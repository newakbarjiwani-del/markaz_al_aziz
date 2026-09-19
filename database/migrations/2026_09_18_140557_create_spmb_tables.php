<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spmb_periode', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spmb_pendaftar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spmb_periode_id')->constrained('spmb_periode')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('nomor_pendaftaran')->unique();
            $table->string('name');
            $table->string('gender', 1)->nullable();
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spmb_pengumuman', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spmb_berita', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('cover_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spmb_galeri', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('caption')->nullable();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spmb_galeri');
        Schema::dropIfExists('spmb_berita');
        Schema::dropIfExists('spmb_pengumuman');
        Schema::dropIfExists('spmb_pendaftar');
        Schema::dropIfExists('spmb_periode');
    }
};
