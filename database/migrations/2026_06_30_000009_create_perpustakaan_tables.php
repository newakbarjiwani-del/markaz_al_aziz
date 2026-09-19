<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('isbn')->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('available')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('peminjaman_buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_id')->constrained('buku')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('dipinjam');
            $table->decimal('fine_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ulasan_buku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buku_id')->constrained('buku')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasan_buku');
        Schema::dropIfExists('peminjaman_buku');
        Schema::dropIfExists('buku');
    }
};
