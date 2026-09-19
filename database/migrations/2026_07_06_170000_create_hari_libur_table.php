<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sekolah_id')->nullable()->constrained('sekolah')->nullOnDelete();
            $table->date('date');
            $table->string('name');
            $table->string('applies_to', 20)->default('both');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['sekolah_id', 'date', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_libur');
    }
};
