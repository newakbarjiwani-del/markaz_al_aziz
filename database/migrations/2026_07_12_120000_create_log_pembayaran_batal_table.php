<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_pembayaran_batal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->nullable()->constrained('pembayaran')->nullOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('original_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference', 50)->nullable();
            $table->string('method', 20)->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->json('items')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_pembayaran_batal');
    }
};
