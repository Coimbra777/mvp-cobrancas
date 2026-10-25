<?php

namespace App\Console\Commands;

use App\Enums\ParcelaStatus;
use App\Models\Parcela;
use App\Services\CobrancaPixService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class GerarCobrancas extends Command
{
    protected $signature = 'cobrancas:gerar';

    protected $description = 'Cria o QR Code Pix das parcelas em aberto que vencem de hoje até daqui a 5 dias';

    public function handle(CobrancaPixService $service): int
    {
        $hoje = today();

        // Janela inteira (não só D-5): recupera dias em que a rotina falhou.
        // Parcelas já vencidas têm QR Code gerado sob demanda na página /p/{token}.
        $parcelas = Parcela::with('emprestimo')
            ->where('status', ParcelaStatus::Aberta)
            ->whereBetween('vencimento', [$hoje, $hoje->copy()->addDays(config('cobranca.dias_antecedencia'))])
            ->orderBy('vencimento')
            ->get();

        $criadas = $existentes = $falhas = 0;

        foreach ($parcelas as $parcela) {
            try {
                $cobranca = $service->garantirQrCodeVigente($parcela);

                if (! $cobranca) {
                    $this->line("Parcela {$parcela->id}: já paga (conciliação)");
                } elseif ($cobranca->wasRecentlyCreated) {
                    $criadas++;
                    $this->info("Parcela {$parcela->id}: QR Code criado ({$cobranca->asaas_qrcode_id})");
                } else {
                    $existentes++;
                }
            } catch (Throwable $e) {
                $falhas++;
                $this->error("Parcela {$parcela->id}: {$e->getMessage()}");
                Log::error('cobrancas:gerar falhou para parcela', ['parcela_id' => $parcela->id, 'erro' => $e->getMessage()]);
            }
        }

        $this->info("Criadas: {$criadas} | Já existentes: {$existentes} | Falhas: {$falhas}");

        return $falhas ? self::FAILURE : self::SUCCESS;
    }
}
