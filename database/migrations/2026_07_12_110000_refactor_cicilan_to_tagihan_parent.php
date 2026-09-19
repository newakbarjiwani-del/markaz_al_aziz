<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayaran_detail', function (Blueprint $table) {
            if (Schema::hasColumn('pembayaran_detail', 'tagihan_cicilan_id')) {
                $table->dropConstrainedForeignId('tagihan_cicilan_id');
            }
        });

        Schema::dropIfExists('tagihan_cicilan');

        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'parent_id')) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('siswa_id')
                    ->constrained('tagihan')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tagihan', 'cicilan_ke')) {
                $table->unsignedTinyInteger('cicilan_ke')->nullable()->after('parent_id');
            }

            if (! Schema::hasColumn('tagihan', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->nullable()->after('amount');
            }

            $table->index(['parent_id', 'cicilan_ke']);
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (Schema::hasColumn('tagihan', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('tagihan', 'cicilan_ke')) {
                $table->dropColumn('cicilan_ke');
            }

            if (Schema::hasColumn('tagihan', 'parent_id')) {
                $table->dropConstrainedForeignId('parent_id');
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
};
