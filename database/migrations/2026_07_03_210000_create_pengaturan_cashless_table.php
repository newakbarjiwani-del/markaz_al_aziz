<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_cashless', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->constrained('sekolah')->cascadeOnDelete();
            $table->decimal('daily_transaction_limit', 15, 2)->default(50000);
            $table->decimal('min_topup', 15, 2)->default(10000);
            $table->boolean('allow_transfer')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['sekolah_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_cashless');
    }
};
