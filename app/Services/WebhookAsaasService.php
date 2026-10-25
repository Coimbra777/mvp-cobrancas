<?php

namespace App\Services;

use App\Models\CobrancaPix;
use App\Models\EventoWebhook;
use Illuminate\Support\Facades\Log;

class WebhookAsaasService
{
    // Eventos que significam dinheiro recebido. Os demais são só gravados.
    private const PAGAMENTO = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'];

    public function __construct(private AsaasClient $asaas)
    {
    }

    public function processar(EventoWebhook $evento): void
    {
        $payment = $evento->payload['payment'] ?? null;

        if (! $payment || ! in_array($evento->evento, self::PAGAMENTO, true)) {
            $this->concluir($evento, 'ignorado');

            return;
        }

        $origem = 'pixQrCodeId';
        $cobranca = $this->porQrCode($payment);

        // Reserva até o 1º evento real confirmar que o pixQrCodeId chega no webhook.
        if (! $cobranca && ! empty($payment['id'])) {
            $origem = 'consulta';
            $cobranca = $this->porQrCode($this->asaas->consultarCobranca($payment['id']));
        }

        if (! $cobranca) {
            // Pix que não veio de um QR Code deste sistema (ex.: pago direto na chave).
            Log::warning('Asaas: pagamento sem cobrança local', ['asaas_payment_id' => $payment['id'] ?? null]);
            $this->concluir($evento, 'nao_encontrado');

            return;
        }

        // Ex.: "baixa:pixQrCodeId" ou "baixa:consulta" (qual caminho encontrou a parcela).
        $this->concluir($evento, $cobranca->registrarPagamento($payment).":{$origem}");
    }

    private function porQrCode(array $payment): ?CobrancaPix
    {
        return empty($payment['pixQrCodeId'])
            ? null
            : CobrancaPix::where('asaas_qrcode_id', $payment['pixQrCodeId'])->first();
    }

    private function concluir(EventoWebhook $evento, string $resultado): void
    {
        $evento->update(['resultado' => $resultado, 'processado_em' => now()]);
    }
}
