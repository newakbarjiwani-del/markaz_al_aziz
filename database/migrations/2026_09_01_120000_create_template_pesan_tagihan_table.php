<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('template_pesan_tagihan')) {
            Schema::create('template_pesan_tagihan', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kategori', 30);
                $table->text('isi_pesan');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at'], 'tpl_pesan_tagihan_nama_deleted_unique');
                $table->index(['kategori', 'is_active'], 'tpl_pesan_tagihan_kategori_active_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('template_pesan_tagihan');
    }
};
