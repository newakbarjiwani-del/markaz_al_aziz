<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_id')->constrained('pembayaran')->cascadeOnDelete();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('sccttran_id')->nullable()->constrained('sccttran')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pembayaran_id', 'tagihan_id']);
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('siswa_id')->nullable()->after('user_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('total_amount', 15, 2)->nullable()->after('siswa_id');
        });

        if (Schema::hasColumn('pembayaran', 'tagihan_id')) {
            $legacyRows = DB::table('pembayaran')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            foreach ($legacyRows as $row) {
                $tagihan = DB::table('tagihan')->where('id', $row->tagihan_id)->first();
                if ($tagihan === null) {
                    continue;
                }

                DB::table('pembayaran')
                    ->where('id', $row->id)
                    ->update([
                        'user_id' => $tagihan->user_id,
                        'siswa_id' => $tagihan->siswa_id,
                        'total_amount' => $row->amount,
                    ]);

                DB::table('pembayaran_detail')->insert([
                    'pembayaran_id' => $row->id,
                    'tagihan_id' => $row->tagihan_id,
                    'amount' => $row->amount,
                    'sccttran_id' => $row->sccttran_id ?? $tagihan->sccttran_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::table('pembayaran', function (Blueprint $table) {
                $table->dropForeign(['tagihan_id']);
                $table->dropConstrainedForeignId('sccttran_id');
                $table->dropColumn(['tagihan_id', 'amount']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->foreignId('sccttran_id')->nullable()->constrained('sccttran')->nullOnDelete();
            $table->string('reference', 50)->nullable();
            $table->string('fidbank', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->foreignId('tagihan_id')->nullable()->constrained('tagihan')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->foreignId('sccttran_id')->nullable()->constrained('sccttran')->nullOnDelete();
        });

        $details = DB::table('pembayaran_detail')->orderBy('id')->get();
        foreach ($details as $detail) {
            DB::table('pembayaran')
                ->where('id', $detail->pembayaran_id)
                ->update([
                    'tagihan_id' => $detail->tagihan_id,
                    'amount' => $detail->amount,
                    'sccttran_id' => $detail->sccttran_id,
                ]);
        }

        Schema::dropIfExists('pembayaran_detail');

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('siswa_id');
            $table->dropColumn('total_amount');
        });
    }
};
