<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sm_topup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('urut')->nullable()->unique()->comment('Synced with id');
            $table->unsignedBigInteger('CUSTID')->nullable();
            $table->unsignedBigInteger('NOMINAL')->nullable();
            $table->char('TOPUPNO', 16)->nullable();
            $table->dateTime('TRXDATE')->nullable();
            $table->timestamps();

            $table->index('CUSTID');
            $table->index('TRXDATE');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sm_topup');
    }
};
