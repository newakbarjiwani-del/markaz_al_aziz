<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_login', function (Blueprint $table) {
            $table->string('browser', 64)->nullable()->after('user_agent');
            $table->string('platform', 64)->nullable()->after('browser');
            $table->string('device', 32)->nullable()->after('platform');
        });
    }

    public function down(): void
    {
        Schema::table('log_login', function (Blueprint $table) {
            $table->dropColumn(['browser', 'platform', 'device']);
        });
    }
};
