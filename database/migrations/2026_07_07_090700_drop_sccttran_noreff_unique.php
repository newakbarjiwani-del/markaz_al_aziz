<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('sccttran', 'sccttran_noreff_unique')) {
            Schema::table('sccttran', function (Blueprint $table) {
                $table->dropUnique(['NOREFF']);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasIndex('sccttran', 'sccttran_noreff_unique')) {
            Schema::table('sccttran', function (Blueprint $table) {
                $table->unique('NOREFF');
            });
        }
    }
};
