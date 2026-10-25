<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_webhook', function (Blueprint $table) {
            $table->id();
            // id do evento no Asaas (evt_...). Chave de idempotência.
            $table->string('chave', 100)->unique();
            $table->string('evento', 60);
            $table->json('payload');
            $table->string('resultado', 40)->nullable();
            $table->dateTime('processado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_webhook');
    }
};
