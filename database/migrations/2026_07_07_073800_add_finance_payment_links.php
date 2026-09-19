<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('sccttran_id')
                ->nullable()
                ->after('tagihan_id')
                ->constrained('sccttran')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sccttran_id');
        });
    }
};
