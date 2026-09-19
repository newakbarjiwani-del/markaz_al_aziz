<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jenis_potongan')) {
            Schema::create('jenis_potongan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
                $table->string('kode', 50)->nullable();
                $table->string('nama');
                $table->string('tipe_default', 10)->default('percent')->comment('percent|fixed');
                $table->unsignedInteger('nilai_default')->default(0);
                $table->text('keterangan')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['nama', 'deleted_at'], 'jenis_potongan_nama_deleted_unique');
                $table->index(['sekolah_id', 'is_active'], 'jenis_potongan_sekolah_active_idx');
            });
        }

        if (! Schema::hasTable('potongan_siswa')) {
            Schema::create('potongan_siswa', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
                $table->foreignId('jenis_potongan_id')->constrained('jenis_potongan')->restrictOnDelete();
                $table->string('tipe', 10)->comment('percent|fixed');
                $table->unsignedInteger('nilai');
                $table->date('berlaku_mulai');
                $table->date('berlaku_sampai');
                $table->unsignedSmallInteger('max_pemakaian')->default(1);
                $table->string('status', 20)->default('aktif')->comment('aktif|nonaktif|habis');
                $table->text('keterangan')->nullable();
                $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['siswa_id', 'status'], 'potongan_siswa_siswa_status_idx');
                $table->index(['sekolah_id', 'status'], 'potongan_siswa_sekolah_status_idx');
                $table->index(['berlaku_mulai', 'berlaku_sampai'], 'potongan_siswa_date_idx');
            });
        }

        if (! Schema::hasTable('potongan_siswa_jenis_tagihan')) {
            Schema::create('potongan_siswa_jenis_tagihan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('potongan_siswa_id')->constrained('potongan_siswa')->cascadeOnDelete();
                $table->foreignId('jenis_tagihan_id')->constrained('jenis_tagihan')->cascadeOnDelete();
                $table->string('tipe', 10)->nullable()->comment('percent|fixed');
                $table->unsignedInteger('nilai')->nullable();
                $table->unsignedSmallInteger('max_pemakaian')->nullable();
                $table->timestamps();

                $table->unique(
                    ['potongan_siswa_id', 'jenis_tagihan_id'],
                    'potongan_siswa_jenis_unique'
                );
            });
        }

        if (! Schema::hasTable('potongan_pemakaian')) {
            Schema::create('potongan_pemakaian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('potongan_siswa_id')->constrained('potongan_siswa')->restrictOnDelete();
                $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
                $table->decimal('amount_bruto', 15, 2);
                $table->decimal('potongan_amount', 15, 2);
                $table->decimal('amount_net', 15, 2);
                $table->unsignedTinyInteger('urutan')->default(1);
                $table->dateTime('applied_at');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['potongan_siswa_id', 'tagihan_id'],
                    'potongan_pemakaian_ps_tagihan_unique'
                );
                $table->index('tagihan_id', 'potongan_pemakaian_tagihan_idx');
            });
        }

        if (Schema::hasTable('tagihan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                if (! Schema::hasColumn('tagihan', 'amount_bruto')) {
                    $table->decimal('amount_bruto', 15, 2)->nullable()->after('amount');
                }
                if (! Schema::hasColumn('tagihan', 'potongan_amount')) {
                    $table->decimal('potongan_amount', 15, 2)->default(0)->after('amount_bruto');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tagihan')) {
            Schema::table('tagihan', function (Blueprint $table) {
                if (Schema::hasColumn('tagihan', 'potongan_amount')) {
                    $table->dropColumn('potongan_amount');
                }
                if (Schema::hasColumn('tagihan', 'amount_bruto')) {
                    $table->dropColumn('amount_bruto');
                }
            });
        }

        Schema::dropIfExists('potongan_pemakaian');
        Schema::dropIfExists('potongan_siswa_jenis_tagihan');
        Schema::dropIfExists('potongan_siswa');
        Schema::dropIfExists('jenis_potongan');
    }
};
