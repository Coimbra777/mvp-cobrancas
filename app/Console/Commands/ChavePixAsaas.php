<?php

namespace App\Console\Commands;

use App\Services\AsaasClient;
use Illuminate\Console\Command;

class ChavePixAsaas extends Command
{
    protected $signature = 'asaas:chave-pix';

    protected $description = 'Mostra a chave Pix usada nos QR Codes (cria uma EVP no Asaas se a conta não tiver nenhuma)';

    public function handle(AsaasClient $asaas): int
    {
        $this->info($asaas->garantirChavePix());

        if (! config('services.asaas.pix_key')) {
            $this->warn('Copie a chave acima para ASAAS_PIX_KEY no .env.');
        }

        return self::SUCCESS;
    }
}
