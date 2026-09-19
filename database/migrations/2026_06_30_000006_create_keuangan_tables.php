<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekening_bank', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('bank');
            $table->string('account_number');
            $table->string('account_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

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

        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_akademik_id')->nullable()->constrained('tahun_akademik')->nullOnDelete();
            $table->foreignId('jenis_tagihan_id')->nullable()->constrained('jenis_tagihan')->nullOnDelete();
            $table->string('jenis');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid', 15, 2)->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('periode')->nullable();
            $table->unsignedInteger('urutan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('saldo_keuangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['siswa_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_keuangan');
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('tagihan');
        Schema::dropIfExists('jenis_tagihan');
        Schema::dropIfExists('rekening_bank');
    }
};
