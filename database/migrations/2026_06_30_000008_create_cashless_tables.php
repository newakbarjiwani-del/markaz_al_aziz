<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dompet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('saldo_us', 15, 2)->default(0);
            $table->decimal('saldo_kantin', 15, 2)->default(0);
            $table->decimal('saldo_tabungan', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['siswa_id', 'deleted_at']);
        });

        Schema::create('transaksi_cashless', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('type');
            $table->string('category')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('wallet')->default('us');
            $table->string('from_wallet')->nullable();
            $table->string('to_wallet')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('menu_kantin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 2);
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('limit_cashless', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->string('type');
            $table->string('target')->nullable();
            $table->string('category')->nullable();
            $table->decimal('daily_limit', 15, 2)->nullable();
            $table->decimal('monthly_limit', 15, 2)->nullable();
            $table->json('blocked_categories')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('alokasi_uang_saku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('period')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pengajuan_uang_saku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_uang_saku');
        Schema::dropIfExists('alokasi_uang_saku');
        Schema::dropIfExists('limit_cashless');
        Schema::dropIfExists('menu_kantin');
        Schema::dropIfExists('transaksi_cashless');
        Schema::dropIfExists('dompet');
    }
};
