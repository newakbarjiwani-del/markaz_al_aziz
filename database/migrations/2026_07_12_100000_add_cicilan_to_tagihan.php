<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'is_cicilan')) {
                $table->unsignedTinyInteger('is_cicilan')->default(0)->after('status');
            }
        });

        Schema::create('tagihan_cicilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid', 15, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->foreignId('pembayaran_detail_id')->nullable()->constrained('pembayaran_detail')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tagihan_id', 'urutan', 'deleted_at']);
        });

        Schema::table('pembayaran_detail', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran_detail', 'tagihan_cicilan_id')) {
                $table->foreignId('tagihan_cicilan_id')
                    ->nullable()
                    ->after('tagihan_id')
                    ->constrained('tagihan_cicilan')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran_detail', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran_detail', 'tagihan_cicilan_id')) {
                $table->dropConstrainedForeignId('tagihan_cicilan_id');
            }
        });

        Schema::dropIfExists('tagihan_cicilan');

        Schema::table('tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan', 'is_cicilan')) {
                $table->dropColumn('is_cicilan');
            }
        });
    }
};
