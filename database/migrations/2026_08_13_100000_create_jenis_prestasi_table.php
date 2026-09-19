<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jenis_prestasi')) {
            Schema::create('jenis_prestasi', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
                $table->string('kode', 50)->nullable();
                $table->string('bidang', 100)->nullable();
                $table->string('nama');
                $table->unsignedInteger('point')->default(0);
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at']);
                $table->index(['sekolah_id', 'is_active']);
            });
        }

        foreach (['prestasi_siswa', 'prestasi_guru'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'jenis_prestasi_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('jenis_prestasi_id')
                        ->nullable()
                        ->after('sekolah_id')
                        ->constrained('jenis_prestasi')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['prestasi_guru', 'prestasi_siswa'] as $tableName) {
            if (Schema::hasColumn($tableName, 'jenis_prestasi_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('jenis_prestasi_id');
                });
            }
        }

        if (Schema::hasTable('jenis_prestasi')) {
            Schema::dropIfExists('jenis_prestasi');
        }
    }
};
