<?php

namespace App\Services;

use App\Enums\CobrancaPixStatus;
use App\Models\CobrancaPix;
use App\Models\Parcela;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CobrancaPixService
{
    // O QR Code da parcela em dia é criado a partir de 5 dias antes do vencimento.
    public const DIAS_ANTECEDENCIA = 5;

    public function __construct(private AsaasClient $asaas)
    {
    }

    public static function referencia(CobrancaPix $cobranca): string
    {
        return "cobranca_pix:{$cobranca->id}";
    }

    public static function disponivelEm(Parcela $parcela): CarbonInterface
    {
        return $parcela->vencimento->copy()->subDays(self::DIAS_ANTECEDENCIA);
    }

    public function dentroDaJanela(Parcela $parcela, CarbonInterface $agora): bool
    {
        return $parcela->vencimento->lte($agora->copy()->startOfDay()->addDays(self::DIAS_ANTECEDENCIA));
    }

    /**
     * Devolve um QR Code vigente para a parcela, criando um novo se preciso.
     * Em dia: expira no fim do dia do vencimento. Atrasada: vale até o fim de hoje
     * (o do dia anterior é substituído), sempre com o valor original da parcela.
     * Retorna null se a parcela está paga ou fora da janela de cobrança.
     */
    public function garantirQrCodeVigente(Parcela $parcela, ?CarbonInterface $agora = null): ?CobrancaPix
    {
        $agora ??= now();

        if (! $parcela->estaAberta() || ! $this->dentroDaJanela($parcela, $agora)) {
            return null;
        }

        // Evita dois QR Codes simultâneos para a mesma parcela (rotina + página abertas juntas).
        return Cache::lock("cobranca-pix:parcela:{$parcela->id}", 60)
            ->block(20, fn () => $this->criarSeNecessario($parcela->fresh('emprestimo'), $agora));
    }

    private function criarSeNecessario(Parcela $parcela, CarbonInterface $agora): CobrancaPix
    {
        $ativas = $parcela->cobrancasPix()->where('status', CobrancaPixStatus::Ativa)->latest('id')->get();

        $vigente = $ativas->first(fn (CobrancaPix $c) => $c->estaVigente($agora));
        if ($vigente) {
            return $vigente;
        }

        // Intenção que ficou sem resposta do Asaas (processo caiu no meio): o payload nunca foi exibido.
        $parcela->cobrancasPix()->where('status', CobrancaPixStatus::Pendente)
            ->update(['status' => CobrancaPixStatus::Falhou, 'erro' => 'Intenção sem resposta do Asaas']);

        $expiraEm = $parcela->diasAtraso($agora) > 0
            ? $agora->copy()->endOfDay()
            : $parcela->vencimento->copy()->endOfDay();

        // 1) grava a intenção, 2) chama o Asaas fora de transação, 3) grava o retorno.
        $cobranca = $parcela->cobrancasPix()->create([
            'status' => CobrancaPixStatus::Pendente,
            'valor_centavos' => $parcela->valor_centavos,
            'expira_em' => $expiraEm,
        ]);

        try {
            $qrCode = $this->asaas->criarQrCodeEstatico($parcela->valor_centavos, $parcela->descricao(), $expiraEm, self::referencia($cobranca));
        } catch (Throwable $e) {
            $cobranca->update(['status' => CobrancaPixStatus::Falhou, 'erro' => $e->getMessage()]);

            throw $e;
        }

        $cobranca->update([
            'status' => CobrancaPixStatus::Ativa,
            'asaas_qrcode_id' => $qrCode['id'],
            'payload' => $qrCode['payload'],
        ]);

        foreach ($ativas as $anterior) {
            $this->substituir($anterior);
        }

        return $cobranca;
    }

    private function substituir(CobrancaPix $cobranca): void
    {
        try {
            $this->asaas->removerQrCodeEstatico($cobranca->asaas_qrcode_id);
        } catch (Throwable $e) {
            // Segue: o QR Code antigo expira sozinho e, se for pago, a baixa acontece mesmo assim.
            Log::warning('Não foi possível remover QR Code substituído no Asaas', [
                'cobranca_pix_id' => $cobranca->id,
                'erro' => $e->getMessage(),
            ]);
        }

        $cobranca->update(['status' => CobrancaPixStatus::Substituida]);
    }
}
