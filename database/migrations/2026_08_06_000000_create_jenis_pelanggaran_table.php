<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jenis_pelanggaran')) {
            Schema::create('jenis_pelanggaran', function (Blueprint $table) {
                $table->id();
                // Universal catalog by default; set to a school to scope it.
                $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
                $table->string('kode', 50)->nullable();
                $table->string('level', 10)->comment('ringan, sedang, berat');
                $table->string('bidang', 100)->comment('Kategori heading, e.g. Kebersihan, Akidah dan Akhlak');
                $table->string('nama');
                $table->unsignedInteger('point')->default(0);
                $table->string('sanction', 50)->nullable()->comment('SP1, SP2, SP3, DO, ganti rugi 10 x lipat');
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at']);
                $table->index(['level', 'bidang']);
                $table->index(['sekolah_id', 'is_active']);
            });
        }

        // Link existing pelanggaran records to the master catalog (nullable —
        // old free-text records keep working untouched).
        foreach (['pelanggaran_siswa', 'pelanggaran_guru'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'jenis_pelanggaran_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('jenis_pelanggaran_id')
                        ->nullable()
                        ->after('sekolah_id')
                        ->constrained('jenis_pelanggaran')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['pelanggaran_guru', 'pelanggaran_siswa'] as $tableName) {
            if (Schema::hasColumn($tableName, 'jenis_pelanggaran_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('jenis_pelanggaran_id');
                });
            }
        }

        if (Schema::hasTable('jenis_pelanggaran')) {
            Schema::dropIfExists('jenis_pelanggaran');
        }
    }
};
