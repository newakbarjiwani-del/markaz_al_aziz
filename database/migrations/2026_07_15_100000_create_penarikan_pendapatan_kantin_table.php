<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penarikan_pendapatan_kantin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kantin_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 10)->default('CASH');
            $table->string('noreff', 32)->unique();
            $table->text('description')->nullable();
            $table->foreignId('settled_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('settled_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kantin_user_id', 'settled_at']);
            $table->index(['sekolah_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penarikan_pendapatan_kantin');
    }
};
