<?php

namespace Tests\Feature;

use App\Services\AsaasClient;
use App\Services\CobrancaPixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\CriaParcelas;
use Tests\TestCase;

class AsaasClientTest extends TestCase
{
    use CriaParcelas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 10:00:00');
    }

    public function test_cria_qr_code_estatico_com_valor_final_e_sem_multa_ou_juros(): void
    {
        $this->fakeAsaas();
        $parcela = $this->criarParcela(['vencimento' => '2026-10-30']);

        $cobranca = app(CobrancaPixService::class)->garantirQrCodeVigente($parcela);

        Http::assertSent(function (Request $request) use ($cobranca) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api-sandbox.asaas.com/v3/pix/qrCodes/static'
                && $request->header('access_token')[0] === 'chave-de-teste'
                && $request->data() === [
                    'addressKey' => 'chave-pix-teste',
                    'value' => 1050.0,
                    'description' => 'Parcela 3 de 10',
                    'expirationDate' => '2026-10-30 23:59:59',
                    'allowsMultiplePayments' => false,
                    'externalReference' => "cobranca_pix:{$cobranca->id}",
                ];
        });
        $this->assertSame('qr_1', $cobranca->asaas_qrcode_id);
        $this->assertSame('ativa', $cobranca->status->value);
    }

    public function test_parcela_atrasada_envia_valor_com_juros_ja_calculado_e_expira_hoje(): void
    {
        $this->fakeAsaas();
        $parcela = $this->criarParcela(['vencimento' => '2026-10-22']); // 3 dias de atraso

        app(CobrancaPixService::class)->garantirQrCodeVigente($parcela);

        Http::assertSent(function (Request $request) {
            return str_ends_with($request->url(), '/pix/qrCodes/static')
                && $request['value'] === 1052.1 // 1.050,00 + 2,10 de juros
                && $request['expirationDate'] === '2026-10-25 23:59:59'
                && ! isset($request['fine'])
                && ! isset($request['interest']);
        });
    }

    public function test_bloqueia_url_de_producao_fora_de_production(): void
    {
        Http::fake();
        config(['services.asaas.url' => 'https://api.asaas.com/v3']);

        try {
            app(AsaasClient::class)->consultarCobranca('pay_1');
            $this->fail('Deveria bloquear a URL de produção.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('sandbox', $e->getMessage());
        }

        Http::assertNothingSent();
    }
}
