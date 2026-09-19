<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->after('id');
            $table->foreignId('sekolah_id')->nullable()->after('username')->constrained('sekolah')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->unsignedTinyInteger('status')->default(1)->after('password');
            $table->unsignedBigInteger('siswa_id')->nullable()->after('status');
            $table->unsignedBigInteger('guru_id')->nullable()->after('siswa_id');
            $table->unsignedBigInteger('orang_tua_id')->nullable()->after('guru_id');
            $table->softDeletes();
            $table->unique(['email', 'deleted_at']);
            $table->unique(['username', 'deleted_at']);
            $table->unique(['phone', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email', 'deleted_at']);
            $table->dropUnique(['username', 'deleted_at']);
            $table->dropUnique(['phone', 'deleted_at']);
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('sekolah_id');
            $table->dropColumn(['username', 'phone', 'status', 'siswa_id', 'guru_id', 'orang_tua_id']);
        });
    }
};
