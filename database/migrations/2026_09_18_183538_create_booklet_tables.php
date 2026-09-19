<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booklet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('cover_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_published', 'published_at']);
        });

        Schema::create('booklet_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booklet_id')->constrained('booklet')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booklet_page');
        Schema::dropIfExists('booklet');
    }
};
