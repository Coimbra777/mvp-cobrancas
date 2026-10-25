<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprestimo_id')->constrained('emprestimos');
            $table->unsignedSmallInteger('numero');
            $table->unsignedBigInteger('valor_centavos');
            $table->date('vencimento');
            $table->string('status', 20)->default('aberta'); // App\Enums\ParcelaStatus
            $table->dateTime('pago_em')->nullable();
            $table->string('token_pagamento', 64)->unique();
            $table->timestamps();

            $table->unique(['emprestimo_id', 'numero']);
            $table->index(['status', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelas');
    }
};
