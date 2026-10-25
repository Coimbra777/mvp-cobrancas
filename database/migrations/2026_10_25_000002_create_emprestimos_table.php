<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprestimos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->unsignedBigInteger('valor_centavos');
            $table->decimal('taxa_mensal', 5, 2); // % ao mês
            $table->string('metodo', 10); // App\Enums\MetodoCalculo
            $table->unsignedSmallInteger('quantidade_parcelas');
            $table->date('primeiro_vencimento');
            $table->unsignedBigInteger('total_centavos');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprestimos');
    }
};
