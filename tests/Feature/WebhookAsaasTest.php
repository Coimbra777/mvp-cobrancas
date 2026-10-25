<?php

namespace Tests\Feature;

use App\Enums\CobrancaPixStatus;
use App\Enums\ParcelaStatus;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\EventoWebhook;
use App\Services\WebhookAsaasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
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
            'payment' => $payment + [
                'id' => 'pay_123',
                'billingType' => 'PIX',
                'value' => 1050.00,
                'netValue' => 1049.01,
                'status' => 'RECEIVED',
                'pixQrCodeId' => $qrCodeId,
                'paymentDate' => '2026-10-25',
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

    public function test_webhook_dispensa_csrf_com_a_protecao_ligada(): void
    {
        // Nos testes o Laravel desliga o CSRF; aqui ele fica ligado de verdade.
        $this->app->bind(VerifyCsrfToken::class, CsrfLigadoNoTeste::class);

        $this->post('/entrar', ['email' => 'a@b.com', 'senha' => 'x'])->assertStatus(419); // controle
        $this->enviar($this->evento('qr_1', [], ['event' => 'PAYMENT_UPDATED']))->assertOk();
    }

    public function test_pagamento_baixa_a_parcela_certa(): void
    {
        $outra = $this->criarParcela(['vencimento' => '2026-10-28']);
        $this->criarCobranca($outra, 'qr_outra');
        $parcela = $this->criarParcela(['vencimento' => '2026-10-30']);
        $cobranca = $this->criarCobranca($parcela, 'qr_certa');

        $this->enviar($this->evento('qr_certa'))->assertOk();

        $this->assertSame(ParcelaStatus::Paga, $parcela->fresh()->status);
        $this->assertTrue($parcela->fresh()->pago_em->isSameDay('2026-10-25'));
        $cobranca->refresh();
        $this->assertSame(CobrancaPixStatus::Paga, $cobranca->status);
        $this->assertSame('pay_123', $cobranca->asaas_payment_id);
        $this->assertSame(105000, $cobranca->valor_pago_centavos);   // value
        $this->assertSame(104901, $cobranca->valor_liquido_centavos); // netValue
        $this->assertSame(ParcelaStatus::Aberta, $outra->fresh()->status);
        $this->assertSame('baixa:pixQrCodeId', EventoWebhook::sole()->resultado);
    }

    public function test_evento_duplicado_e_processado_uma_vez(): void
    {
        $this->criarCobranca($this->criarParcela(), 'qr_1');
        $evento = $this->evento('qr_1');

        $this->enviar($evento)->assertOk();
        $this->enviar($evento)->assertOk()->assertJson(['duplicado' => true]);

        $this->assertSame(1, EventoWebhook::count());
    }

    public function test_confirmed_e_received_do_mesmo_pix_baixam_uma_vez(): void
    {
        $this->criarCobranca($this->criarParcela(), 'qr_1');

        $this->enviar($this->evento('qr_1', ['status' => 'CONFIRMED'], ['event' => 'PAYMENT_CONFIRMED']))->assertOk();
        $this->enviar($this->evento('qr_1'))->assertOk();

        $this->assertSame(['baixa:pixQrCodeId', 'ja_baixada:pixQrCodeId'], EventoWebhook::orderBy('id')->pluck('resultado')->all());
    }

    public function test_sem_pix_qr_code_id_consulta_o_pagamento_no_asaas(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');
        Http::fake(['*/payments/pay_123' => Http::response(['id' => 'pay_123', 'pixQrCodeId' => 'qr_1', 'value' => 1050.00])]);

        $this->enviar($this->evento('', ['pixQrCodeId' => null]))->assertOk();

        $this->assertSame(ParcelaStatus::Paga, $parcela->fresh()->status);
        $this->assertSame('baixa:consulta', EventoWebhook::sole()->resultado);
    }

    public function test_parcela_ja_paga_por_baixa_manual_nao_e_paga_de_novo(): void
    {
        $parcela = $this->criarParcela();
        $cobranca = $this->criarCobranca($parcela, 'qr_1');
        $parcela->darBaixa(Carbon::parse('2026-10-24 15:00'));

        $this->enviar($this->evento('qr_1'))->assertOk();

        $this->assertTrue($parcela->fresh()->pago_em->isSameDay('2026-10-24'));
        $this->assertSame('pay_123', $cobranca->fresh()->asaas_payment_id);
        $this->assertSame('parcela_ja_paga:pixQrCodeId', EventoWebhook::sole()->resultado);
    }

    public function test_falha_responde_500_e_o_reenvio_processa(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');
        $evento = $this->evento('qr_1');

        $this->mock(WebhookAsaasService::class)->shouldReceive('processar')->once()->andThrow(new RuntimeException('instável'));
        $this->enviar($evento)->assertStatus(500);
        $this->assertNull(EventoWebhook::sole()->processado_em);

        $this->forgetMock(WebhookAsaasService::class);
        $this->enviar($evento)->assertOk()->assertJson(['recebido' => true]);
        $this->assertSame(ParcelaStatus::Paga, $parcela->fresh()->status);
    }

    public function test_eventos_que_nao_sao_de_pagamento_so_sao_gravados(): void
    {
        $parcela = $this->criarParcela();
        $this->criarCobranca($parcela, 'qr_1');

        $this->enviar($this->evento('qr_1', [], ['event' => 'PAYMENT_REFUNDED']))->assertOk();

        $this->assertSame(ParcelaStatus::Aberta, $parcela->fresh()->status);
        $this->assertSame('ignorado', EventoWebhook::sole()->resultado);
    }
}

class CsrfLigadoNoTeste extends VerifyCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
