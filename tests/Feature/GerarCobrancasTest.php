<?php

namespace Tests\Feature;

use App\Enums\ParcelaStatus;
use App\Models\CobrancaPix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\CriaParcelas;
use Tests\TestCase;

class GerarCobrancasTest extends TestCase
{
    use CriaParcelas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 07:00:00');
    }

    private function criacoesDeQrCode(): int
    {
        return Http::recorded(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/pix/qrCodes/static'))->count();
    }

    public function test_recupera_parcela_que_ficou_para_tras_e_nao_duplica(): void
    {
        $this->fakeAsaas();

        // D-5 foi há 3 dias e a rotina falhou: ainda deve ser gerada.
        $atrasadaNaRotina = $this->criarParcela(['vencimento' => '2026-10-27']);
        $longe = $this->criarParcela(['vencimento' => '2026-11-05']);
        $paga = $this->criarParcela(['vencimento' => '2026-10-26', 'status' => ParcelaStatus::Paga]);

        $this->artisan('cobrancas:gerar')->assertSuccessful();
        $this->artisan('cobrancas:gerar')->assertSuccessful();

        $this->assertSame(1, $this->criacoesDeQrCode());
        $this->assertSame(1, $atrasadaNaRotina->cobrancasPix()->count());
        $this->assertSame(0, $longe->cobrancasPix()->count());
        $this->assertSame(0, $paga->cobrancasPix()->count());
    }

    public function test_falha_no_asaas_marca_intencao_e_proxima_execucao_recupera(): void
    {
        $parcela = $this->criarParcela(['vencimento' => '2026-10-28']);

        Http::fake(['*/pix/qrCodes/static' => Http::sequence()
            ->push(['errors' => [['description' => 'instável']]], 500)
            ->push(['id' => 'qr_1', 'payload' => '00020126PAYLOADqr_1'])]);

        $this->artisan('cobrancas:gerar')->assertFailed();
        $this->assertSame('falhou', $parcela->cobrancasPix()->sole()->status->value);

        $this->artisan('cobrancas:gerar')->assertSuccessful();
        $this->assertSame('qr_1', $parcela->cobrancasPix()->where('status', 'ativa')->sole()->asaas_qrcode_id);
    }

    public function test_valor_mudou_gera_novo_qr_code_e_remove_o_anterior(): void
    {
        $this->fakeAsaas(['qr_novo']);
        $parcela = $this->criarParcela(['vencimento' => '2026-10-28']);
        $antigo = $this->criarCobranca($parcela, 'qr_antigo');

        // Pagamento parcial em dinheiro: valor em aberto mudou
        $parcela->registrarPagamento(5000, now());

        $this->artisan('cobrancas:gerar')->assertSuccessful();

        $this->assertSame('substituida', $antigo->fresh()->status->value);
        $novo = CobrancaPix::where('asaas_qrcode_id', 'qr_novo')->sole();
        $this->assertSame(100000, $novo->valor_centavos);
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/pix/qrCodes/static/qr_antigo'));
        // Conferiu no Asaas se o QR Code antigo tinha sido pago antes de trocar
        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'pixQrCodeId=qr_antigo'));
    }
}
