<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sccttran_cashless', function (Blueprint $table) {
            if (! Schema::hasColumn('sccttran_cashless', 'description')) {
                $table->text('description')->nullable()->after('TRANSNO');
            }
        });

        if (Schema::hasColumn('sccttran_cashless', 'description')) {
            DB::table('sccttran_cashless')
                ->whereNull('description')
                ->whereNotNull('TRANSNO')
                ->where('TRANSNO', '!=', '')
                ->update(['description' => DB::raw('TRANSNO')]);
        }
    }

    public function down(): void
    {
        Schema::table('sccttran_cashless', function (Blueprint $table) {
            if (Schema::hasColumn('sccttran_cashless', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
