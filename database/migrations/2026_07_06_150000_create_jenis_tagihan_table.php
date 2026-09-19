<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jenis_tagihan')) {
            Schema::create('jenis_tagihan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('description')->nullable();
                $table->decimal('default_amount', 15, 2)->nullable();
                $table->boolean('is_spp')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['sekolah_id', 'name', 'deleted_at']);
            });
        }

        if (Schema::hasTable('tagihan') && ! Schema::hasColumn('tagihan', 'jenis_tagihan_id')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->foreignId('jenis_tagihan_id')
                    ->nullable()
                    ->after('tahun_akademik_id')
                    ->constrained('jenis_tagihan')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tagihan', 'jenis_tagihan_id')) {
            Schema::table('tagihan', function (Blueprint $table) {
                $table->dropConstrainedForeignId('jenis_tagihan_id');
            });
        }

        if (Schema::hasTable('jenis_tagihan')) {
            Schema::dropIfExists('jenis_tagihan');
        }
    }
};
