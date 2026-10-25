<?php

namespace App\Services;

use App\Models\CobrancaPix;
use App\Models\EventoWebhook;
use Illuminate\Support\Facades\Log;

class WebhookAsaasService
{
    // Eventos que significam dinheiro recebido
    private const BAIXA = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'];

    // Eventos que só registram e alertam; reverter baixa é decisão manual
    private const ALERTA = [
        'PAYMENT_DELETED',
        'PAYMENT_REFUNDED',
        'PAYMENT_PARTIALLY_REFUNDED',
        'PAYMENT_REFUND_IN_PROGRESS',
        'PAYMENT_RECEIVED_IN_CASH_UNDONE',
        'PAYMENT_CHARGEBACK_REQUESTED',
        'PAYMENT_CHARGEBACK_DISPUTE',
        'PAYMENT_AWAITING_CHARGEBACK_REVERSAL',
    ];

    public function __construct(
        private AsaasClient $asaas,
        private BaixaPixService $baixa,
    ) {}

    public function processar(EventoWebhook $evento): void
    {
        $payment = $evento->payload['payment'] ?? null;

        if (! $payment || (! in_array($evento->evento, self::BAIXA) && ! in_array($evento->evento, self::ALERTA))) {
            $this->concluir($evento, 'ignorado');

            return;
        }

        [$cobranca, $origem] = $this->localizar($payment);

        if (in_array($evento->evento, self::ALERTA)) {
            Log::warning("Asaas: {$evento->evento}", [
                'asaas_payment_id' => $payment['id'] ?? null,
                'cobranca_pix_id' => $cobranca?->id,
                'parcela_id' => $cobranca?->parcela_id,
            ]);
            $this->concluir($evento, $cobranca ? 'alerta' : 'alerta:nao_encontrado');

            return;
        }

        // Alternativa: o evento não trouxe pixQrCodeId/externalReference reconhecível; consulta a cobrança.
        if (! $cobranca && isset($payment['id'])) {
            $consultado = $this->asaas->consultarCobranca($payment['id']);
            [$cobranca, $origem] = $this->localizar($consultado);
            $origem = $cobranca ? "consulta:{$origem}" : null;
        }

        if (! $cobranca) {
            // Pix recebido que não veio de um QR Code deste sistema (ex.: pago direto na chave)
            Log::warning('Asaas: pagamento sem cobrança local', ['asaas_payment_id' => $payment['id'] ?? null]);
            $this->concluir($evento, 'nao_encontrado');

            return;
        }

        $resultado = $this->baixa->executar($cobranca, $payment);

        // Ex.: "baixa:pixQrCodeId" ou "baixa:consulta:pixQrCodeId" (qual caminho encontrou a parcela)
        $this->concluir($evento, "{$resultado}:{$origem}");
    }

    /**
     * @return array{0: ?CobrancaPix, 1: ?string}
     */
    private function localizar(array $payment): array
    {
        if (! empty($payment['pixQrCodeId'])
            && $cobranca = CobrancaPix::where('asaas_qrcode_id', $payment['pixQrCodeId'])->first()) {
            return [$cobranca, 'pixQrCodeId'];
        }

        $referencia = (string) ($payment['externalReference'] ?? '');
        if (str_starts_with($referencia, 'cobranca_pix:')
            && $cobranca = CobrancaPix::find((int) substr($referencia, strlen('cobranca_pix:')))) {
            return [$cobranca, 'externalReference'];
        }

        if (! empty($payment['id'])
            && $cobranca = CobrancaPix::where('asaas_payment_id', $payment['id'])->first()) {
            return [$cobranca, 'paymentId'];
        }

        return [null, null];
    }

    private function concluir(EventoWebhook $evento, string $resultado): void
    {
        $evento->update(['resultado' => $resultado, 'erro' => null, 'processado_em' => now()]);
    }
}
