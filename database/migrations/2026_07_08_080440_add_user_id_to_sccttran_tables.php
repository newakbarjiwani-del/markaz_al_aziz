<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sccttran', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('CUSTID')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('user_id');
        });

        Schema::table('sccttran_cashless', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('CUSTID')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sccttran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('sccttran_cashless', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
