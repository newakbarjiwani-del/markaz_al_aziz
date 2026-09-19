<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_login', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 32);
            $table->string('status', 16);
            $table->string('identifier')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('portal_access_token_id')->nullable()->constrained('portal_access_tokens')->nullOnDelete();
            $table->string('message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['created_at', 'method', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_login');
    }
};
