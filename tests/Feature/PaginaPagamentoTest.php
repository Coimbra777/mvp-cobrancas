<?php

namespace Tests\Feature;

use App\Enums\ParcelaStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\CriaParcelas;
use Tests\TestCase;

class PaginaPagamentoTest extends TestCase
{
    use CriaParcelas, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-25 10:00:00');
    }

    private function criacoesDeQrCode(): int
    {
        return Http::recorded(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/pix/qrCodes/static'))->count();
    }

    public function test_parcela_paga_mostra_paga_sem_chamar_o_asaas(): void
    {
        Http::fake();
        $parcela = $this->criarParcela(['status' => ParcelaStatus::Paga, 'valor_pago_centavos' => 105000, 'pago_em' => now()]);

        $this->get("/p/{$parcela->token_pagamento}")
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('noindex', false)
            ->assertSee('Paga')
            ->assertSee('Olá, Maria')
            ->assertDontSee('Silva')
            ->assertDontSee('Copiar código Pix');

        Http::assertNothingSent();
    }

    public function test_atrasada_gera_qr_code_sob_demanda_e_reaproveita_no_mesmo_dia(): void
    {
        $this->fakeAsaas();
        $parcela = $this->criarParcela(['vencimento' => '2026-10-22']);

        $this->get("/p/{$parcela->token_pagamento}")
            ->assertOk()
            ->assertSee('R$ 1.052,10')
            ->assertSee('Em atraso há 3 dias')
            ->assertSee('00020126PAYLOADqr_1')
            ->assertSee('<svg', false)
            ->assertSee('Copiar código Pix');

        $this->get("/p/{$parcela->token_pagamento}")->assertOk();
        $this->assertSame(1, $this->criacoesDeQrCode());
    }

    public function test_no_dia_seguinte_o_valor_muda_e_o_qr_code_e_substituido(): void
    {
        $this->fakeAsaas();
        $parcela = $this->criarParcela(['vencimento' => '2026-10-22']);
        $this->get("/p/{$parcela->token_pagamento}");

        Carbon::setTestNow('2026-10-26 09:00:00');
        $this->get("/p/{$parcela->token_pagamento}")->assertSee('R$ 1.052,80')->assertSee('00020126PAYLOADqr_2');

        $this->assertSame(['substituida', 'ativa'], $parcela->cobrancasPix()->orderBy('id')->get()->map(fn ($c) => $c->status->value)->all());
    }

    public function test_fora_da_janela_informa_quando_o_pix_fica_disponivel(): void
    {
        Http::fake();
        $parcela = $this->criarParcela(['vencimento' => '2026-11-20']);

        $this->get("/p/{$parcela->token_pagamento}")->assertOk()->assertSee('a partir de 15/11/2026');
        Http::assertNothingSent();
    }

    public function test_token_desconhecido_ou_id_sequencial_responde_404(): void
    {
        $parcela = $this->criarParcela();

        $this->get('/p/token-inexistente')->assertNotFound();
        $this->get("/p/{$parcela->id}")->assertNotFound();
        $this->assertGreaterThanOrEqual(40, strlen($parcela->token_pagamento));
    }
}
