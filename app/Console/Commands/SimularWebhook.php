<?php

namespace App\Console\Commands;

use App\Enums\CobrancaPixStatus;
use App\Models\EventoWebhook;
use App\Models\Parcela;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SimularWebhook extends Command
{
    protected $signature = 'webhook:simular
        {parcela : id da parcela}
        {--evento=PAYMENT_RECEIVED : PAYMENT_RECEIVED, PAYMENT_CONFIRMED, PAYMENT_REFUNDED...}
        {--payment= : reaproveita um payment id (ex.: CONFIRMED e depois RECEIVED do mesmo Pix)}
        {--url= : endpoint do webhook (padrão: APP_URL/webhooks/asaas)}';

    protected $description = 'Envia ao endpoint um evento de pagamento no formato do Asaas com o pixQrCodeId da parcela';

    public function handle(): int
    {
        $parcela = Parcela::findOrFail($this->argument('parcela'));

        $cobranca = $parcela->cobrancasPix()
            ->whereNotNull('asaas_qrcode_id')
            ->orderByRaw('status = ? desc', [CobrancaPixStatus::Ativa->value])
            ->latest('id')
            ->first();

        if (! $cobranca) {
            $this->error('A parcela não tem QR Code. Rode `php artisan cobrancas:gerar` ou abra a página /p/{token}.');

            return self::FAILURE;
        }

        $valor = $cobranca->valor_centavos / 100;
        $evento = $this->option('evento');
        $paymentId = $this->option('payment') ?: 'pay_simulado_'.Str::lower(Str::random(12));

        // Usa o último evento real recebido como molde; sem ele, o formato da documentação.
        $molde = EventoWebhook::where('chave', 'like', 'evt_%')
            ->where('chave', 'not like', 'evt_simulado_%')
            ->whereNotNull('asaas_payment_id')
            ->latest('id')
            ->value('payload');

        $payment = array_merge($molde['payment'] ?? [
            'object' => 'payment',
            'customer' => null,
            'billingType' => 'PIX',
            'originalValue' => null,
            'interestValue' => null,
            'invoiceUrl' => null,
            'deleted' => false,
        ], [
            'id' => $paymentId,
            'dateCreated' => today()->toDateString(),
            'dueDate' => today()->toDateString(),
            'originalDueDate' => today()->toDateString(),
            'value' => $valor,
            'netValue' => round($valor - 0.99, 2),
            'description' => $parcela->descricao(),
            'externalReference' => null,
            'pixQrCodeId' => $cobranca->asaas_qrcode_id,
            'status' => $evento === 'PAYMENT_CONFIRMED' ? 'CONFIRMED' : 'RECEIVED',
            'confirmedDate' => today()->toDateString(),
            'paymentDate' => today()->toDateString(),
            'clientPaymentDate' => today()->toDateString(),
        ]);

        $corpo = [
            'id' => 'evt_simulado_'.Str::lower(Str::random(16)),
            'event' => $evento,
            'dateCreated' => now()->format('Y-m-d H:i:s'),
            'payment' => $payment,
        ];

        $url = $this->option('url') ?: rtrim(config('app.url'), '/').'/webhooks/asaas';

        $this->line("Enviando {$evento} (".($molde ? 'molde: último evento real' : 'molde: documentação').") para {$url}");
        $this->line("pixQrCodeId={$cobranca->asaas_qrcode_id} payment={$paymentId} valor={$valor}");

        $response = Http::timeout(15)
            ->withHeaders(['asaas-access-token' => (string) config('services.asaas.webhook_token')])
            ->post($url, $corpo);

        $this->line("HTTP {$response->status()}: {$response->body()}");

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
