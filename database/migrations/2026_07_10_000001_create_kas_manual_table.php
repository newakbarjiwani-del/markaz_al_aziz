<?php

use App\Models\User;
use App\Models\Sekolah;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_manual', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Sekolah::class, 'sekolah_id')->nullable()->constrained()->nullOnDelete();
            $table->date('tanggal');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->string('kategori', 100);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('jumlah');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_manual');
    }
};
