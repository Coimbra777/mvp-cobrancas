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
    public function __construct(
        private AsaasClient $asaas,
        private BaixaPixService $baixa,
    ) {}

    public static function referencia(CobrancaPix $cobranca): string
    {
        return "cobranca_pix:{$cobranca->id}";
    }

    public function dentroDaJanela(Parcela $parcela, CarbonInterface $agora): bool
    {
        $limite = $agora->copy()->startOfDay()->addDays(config('cobranca.dias_antecedencia'));

        return $parcela->vencimento->lte($limite);
    }

    /**
     * Devolve um QR Code válido para o valor devido hoje, criando um novo se preciso.
     * Em dia: expira no fim do dia do vencimento. Em atraso: o valor muda por dia,
     * então o QR Code vale só até o fim de hoje.
     * Retorna null se a parcela não está aberta ou ainda está fora da janela de cobrança.
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

    private function criarSeNecessario(Parcela $parcela, CarbonInterface $agora): ?CobrancaPix
    {
        $valor = $parcela->valorDevidoCentavos($agora);

        $ativas = $parcela->cobrancasPix()->where('status', CobrancaPixStatus::Ativa)->latest('id')->get();

        $vigente = $ativas->first(fn (CobrancaPix $c) => $c->valor_centavos === $valor && $c->estaVigente($agora));
        if ($vigente) {
            return $vigente;
        }

        // Antes de trocar, confere se algum QR Code anterior já foi pago e o webhook se perdeu.
        foreach ($ativas as $anterior) {
            if ($this->conciliar($anterior)) {
                return null;
            }
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
            'valor_centavos' => $valor,
            'expira_em' => $expiraEm,
        ]);

        try {
            $qrCode = $this->asaas->criarQrCodeEstatico($valor, $parcela->descricao(), $expiraEm, self::referencia($cobranca));
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

    /**
     * Se o QR Code já tem pagamento no Asaas, dá a baixa. Retorna true se baixou.
     */
    private function conciliar(CobrancaPix $cobranca): bool
    {
        if (! $cobranca->asaas_qrcode_id) {
            return false;
        }

        $pagamentos = collect($this->asaas->consultarCobrancasPorQrCode($cobranca->asaas_qrcode_id))
            ->whereIn('status', ['CONFIRMED', 'RECEIVED']);

        foreach ($pagamentos as $pagamento) {
            Log::warning('Pix pago sem webhook processado; baixa feita pela conciliação', [
                'cobranca_pix_id' => $cobranca->id,
                'asaas_payment_id' => $pagamento['id'],
            ]);
            $this->baixa->executar($cobranca, $pagamento);
        }

        return $pagamentos->isNotEmpty();
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
