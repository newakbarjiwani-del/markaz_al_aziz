<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            if (! Schema::hasColumn('tagihan', 'sccttran_id')) {
                $table->foreignId('sccttran_id')->nullable()->after('urutan')
                    ->constrained('sccttran')->nullOnDelete();
            }

            if (! Schema::hasColumn('tagihan', 'reference')) {
                $table->string('reference', 50)->nullable()->after('sccttran_id');
            }

            if (! Schema::hasColumn('tagihan', 'fidbank')) {
                $table->string('fidbank', 50)->nullable()->after('reference');
            }

            if (! Schema::hasColumn('tagihan', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('fidbank')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sccttran_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['reference', 'fidbank']);
        });
    }
};
