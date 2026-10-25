<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas_pix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcela_id')->constrained('parcelas');
            $table->string('status', 20)->default('pendente'); // App\Enums\CobrancaPixStatus
            $table->unsignedBigInteger('valor_centavos');
            $table->dateTime('expira_em');
            $table->string('asaas_qrcode_id', 100)->nullable()->unique();
            $table->text('payload')->nullable();
            $table->text('erro')->nullable();

            // Preenchidos na baixa pelo webhook
            $table->string('asaas_payment_id', 100)->nullable()->unique();
            $table->dateTime('pago_em')->nullable();
            $table->unsignedBigInteger('valor_pago_centavos')->nullable();    // payment.value
            $table->unsignedBigInteger('valor_liquido_centavos')->nullable(); // payment.netValue
            $table->bigInteger('diferenca_centavos')->nullable();             // pago - devido no dia
            $table->timestamps();

            $table->index(['parcela_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas_pix');
    }
};
