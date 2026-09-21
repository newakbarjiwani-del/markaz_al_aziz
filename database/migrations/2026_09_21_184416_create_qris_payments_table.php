<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qris_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->string('vano', 32);
            $table->decimal('amount', 15, 2);
            $table->string('qris_id', 64)->nullable()->unique();
            $table->string('transaction_id', 32);
            $table->string('lazismu_transaction_id', 64)->nullable();
            $table->string('account_no', 32)->nullable();
            $table->string('mitra_customer_id', 100)->nullable();
            $table->text('raw_qr_data')->nullable();
            $table->string('merchant_id', 64)->nullable();
            $table->string('merchant_pan', 64)->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('paid_flag')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('pembayaran_id')->nullable()->constrained('pembayaran')->nullOnDelete();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('push_payload')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vano', 'status']);
            $table->index(['siswa_id', 'status']);
        });

        Schema::create('qris_payment_tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qris_payment_id')->constrained('qris_payments')->cascadeOnDelete();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['qris_payment_id', 'tagihan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qris_payment_tagihan');
        Schema::dropIfExists('qris_payments');
    }
};
