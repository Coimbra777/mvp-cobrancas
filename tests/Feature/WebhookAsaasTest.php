<?php

namespace Tests\Feature;

use App\Enums\CobrancaPixStatus;
use App\Enums\ParcelaStatus;
use App\Models\EventoWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\CriaParcelas;
use Tests\TestCase;

class WebhookAsaasTest extends TestCase
{
    use CriaParcelas, RefreshDatabase;

    private const TOKEN = 'token-webhook-de-teste-com-mais-de-32-caracteres';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 10:00:00');
        Http::preventStrayRequests();
    }

    private function evento(string $qrCodeId, array $payment = [], array $evento = []): array
    {
        return $evento + [
            'id' => 'evt_'.uniqid(),
            'event' => 'PAYMENT_RECEIVED',
            'dateCreated' => '2026-10-25 10:00:00',
            'payment' => $payment + [
                'object' => 'payment',
                'id' => 'pay_123',
                'billingType' => 'PIX',
                'value' => 1050.00,
                'netValue' => 1049.01,
                'status' => 'RECEIVED',
                'pixQrCodeId' => $qrCodeId,
                'paymentDate' => '2026-10-25',
                'clientPaymentDate' => '2026-10-25',
            ],
        ];
    }

    private function enviar(array $corpo, ?string $token = self::TOKEN)
    {
        return $this->postJson('/webhooks/asaas', $corpo, $token ? ['asaas-access-token' => $token] : []);
    }

    public function test_token_invalido_ou_ausente_responde_401_e_nao_grava(): void
    {
        $this->enviar($this->evento('qr_1'), 'token-errado')->assertStatus(401);
        $this->enviar($this->evento('qr_1'), null)->assertStatus(401);

        $this->assertSame(0, EventoWebhook::count());
    }

    public function test_evento_valido_baixa_a_parcela_certa(): void
    {
        $outra = $this->criarParcela(['vencimento' => '2026-10-28']);
        $this->criarCobranca($outra, 'qr_outra');
        $parcela = $this->criarParcela(['vencimento' => '2026-10-30']);
        $cobranca = $this->criarCobranca($parcela, 'qr_certa');

        $this->enviar($this->evento('qr_certa'))->assertOk();

        $parcela->refresh();
        $this->assertSame(ParcelaStatus::Paga, $parcela->status);
        $this->assertSame(105000, $parcela->valor_pago_centavos);
        $this->assertTrue($parcela->pago_em->isSameDay('2026-10-25'));

        $cobranca->refresh();
        $this->assertSame(CobrancaPixStatus::Paga, $cobranca->status);
        $this->assertSame('pay_123', $cobranca->asaas_payment_id);
        $this->assertSame(105000, $cobranca->valor_pago_centavos);   // value
        $this->assertSame(104901, $cobranca->valor_liquido_centavos); // netValue
        $this->assertSame(0, $cobranca->diferenca_centavos);

        $this->assertSame(ParcelaStatus::Aberta, $outra->fresh()->status);
        $this->assertSame('baixa:pixQrCodeId', EventoWebhook::sole()->resultado);
    }

    public function test_evento_duplicado_e_processado_uma_vez(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');
        $evento = $this->evento('qr_1');

        $this->enviar($evento)->assertOk();
        $this->enviar($evento)->assertOk()->assertJson(['duplicado' => true]);

        $this->assertSame(1, EventoWebhook::count());
        $this->assertSame(105000, $parcela->fresh()->valor_pago_centavos);
    }

    public function test_confirmed_e_received_do_mesmo_pix_baixam_uma_vez(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');

        $this->enviar($this->evento('qr_1', ['status' => 'CONFIRMED'], ['event' => 'PAYMENT_CONFIRMED']))->assertOk();
        $this->enviar($this->evento('qr_1'))->assertOk();

        $this->assertSame(105000, $parcela->fresh()->valor_pago_centavos);
        $this->assertSame(['baixa:pixQrCodeId', 'ja_baixada:pixQrCodeId'], EventoWebhook::orderBy('id')->pluck('resultado')->all());
    }

    public function test_pagamento_de_qr_code_substituido_baixa_e_registra_diferenca(): void
    {
        Log::spy();
        $parcela = $this->criarParcela(['vencimento' => '2026-10-22']); // 3 dias de atraso: devido 1.052,10
        $antigo = $this->criarCobranca($parcela, 'qr_antigo', ['status' => CobrancaPixStatus::Substituida]);

        $this->enviar($this->evento('qr_antigo', ['value' => 1051.40]))->assertOk(); // valor de ontem

        $this->assertSame(ParcelaStatus::Paga, $parcela->fresh()->status);
        $this->assertSame(-70, $antigo->fresh()->diferenca_centavos);
        Log::shouldHaveReceived('warning')->with('Pix recebido com divergência', Mockery::on(
            fn ($ctx) => $ctx['qrcode_substituido'] === true && $ctx['diferenca_centavos'] === -70
        ));
    }

    public function test_sem_pix_qr_code_id_consulta_a_cobranca_no_asaas(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');
        Http::fake(['*/payments/pay_123' => Http::response(['id' => 'pay_123', 'pixQrCodeId' => 'qr_1', 'value' => 1050.00])]);

        $this->enviar($this->evento('', ['pixQrCodeId' => null]))->assertOk();

        $this->assertSame(ParcelaStatus::Paga, $parcela->fresh()->status);
        $this->assertSame('baixa:consulta:pixQrCodeId', EventoWebhook::sole()->resultado);
    }

    public function test_estorno_so_registra_e_nao_mexe_na_parcela(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1', ['asaas_payment_id' => 'pay_123']);

        $this->enviar($this->evento('qr_1', [], ['event' => 'PAYMENT_REFUNDED']))->assertOk();
        $this->enviar($this->evento('qr_1', [], ['event' => 'PAYMENT_UPDATED']))->assertOk();

        $this->assertSame(ParcelaStatus::Aberta, $parcela->fresh()->status);
        $this->assertSame(['alerta', 'ignorado'], EventoWebhook::orderBy('id')->pluck('resultado')->all());
    }
}
