<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sccttran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('urut')->nullable()->unique()->comment('Synced with id');
            $table->unsignedBigInteger('CUSTID')->nullable();
            $table->char('METODE', 15)->nullable();
            $table->dateTime('TRXDATE');
            $table->char('NOREFF', 40)->nullable();
            $table->char('FIDBANK', 10)->nullable();
            $table->char('KDCHANNEL', 5)->nullable();
            $table->unsignedBigInteger('DEBET')->default(0);
            $table->unsignedBigInteger('KREDIT')->default(0);
            $table->char('REFFBANK', 14)->nullable();
            $table->char('TRANSNO', 50)->nullable();
            $table->timestamps();

            $table->index('CUSTID');
            $table->index('TRXDATE');
            $table->index('NOREFF');
            $table->index('TRANSNO');
        });

        Schema::create('sccttran_cashless', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('urut')->nullable()->unique()->comment('Synced with id');
            $table->unsignedBigInteger('CUSTID')->nullable();
            $table->char('METODE', 15)->nullable();
            $table->dateTime('TRXDATE');
            $table->char('NOREFF', 40)->nullable();
            $table->char('FIDBANK', 10)->nullable();
            $table->char('KDCHANNEL', 5)->nullable();
            $table->unsignedBigInteger('DEBET')->default(0);
            $table->unsignedBigInteger('KREDIT')->default(0);
            $table->char('REFFBANK', 14)->nullable();
            $table->char('TRANSNO', 50)->nullable();
            $table->timestamps();

            $table->index('CUSTID');
            $table->index('TRXDATE');
            $table->index('NOREFF');
            $table->index('TRANSNO');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sccttran_cashless');
        Schema::dropIfExists('sccttran');
    }
};
