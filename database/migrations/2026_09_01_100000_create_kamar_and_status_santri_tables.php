<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kamar')) {
            Schema::create('kamar', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->nullable();
                $table->string('nama');
                $table->string('blok', 100)->nullable();
                $table->unsignedSmallInteger('kapasitas')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at'], 'kamar_nama_deleted_unique');
            });
        }

        if (! Schema::hasTable('status_santri')) {
            Schema::create('status_santri', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at'], 'status_santri_nama_deleted_unique');
            });
        }

        Schema::table('siswa', function (Blueprint $table) {
            if (! Schema::hasColumn('siswa', 'kamar_id')) {
                $table->foreignId('kamar_id')->nullable()->after('kelas_id')->constrained('kamar')->nullOnDelete();
            }
            if (! Schema::hasColumn('siswa', 'status_santri_id')) {
                $table->foreignId('status_santri_id')->nullable()->after('kamar_id')->constrained('status_santri')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            if (Schema::hasColumn('siswa', 'status_santri_id')) {
                $table->dropConstrainedForeignId('status_santri_id');
            }
            if (Schema::hasColumn('siswa', 'kamar_id')) {
                $table->dropConstrainedForeignId('kamar_id');
            }
        });

        Schema::dropIfExists('status_santri');
        Schema::dropIfExists('kamar');
    }
};
