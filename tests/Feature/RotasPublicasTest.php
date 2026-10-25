<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\CriaParcelas;
use Tests\TestCase;

class RotasPublicasTest extends TestCase
{
    use CriaParcelas, RefreshDatabase;

    // Únicas rotas que podem responder sem sessão.
    private const ROTAS_PUBLICAS = [
        '/',                          // só redireciona para /painel (protegido)
        'entrar',
        'p/{token}',
        'webhooks/asaas',
        '_inertia/devtools/entries',      // do Inertia: só existem com APP_ENV=local
        '_inertia/devtools/entries/{id}',
    ];

    public function test_webhook_dispensa_csrf_e_sessao_com_a_protecao_ligada(): void
    {
        // Nos testes o Laravel desliga o CSRF; aqui ele fica ligado de verdade.
        $this->app->bind(VerifyCsrfToken::class, CsrfLigadoNoTeste::class);

        // Controle: uma rota comum sem token recebe 419.
        $this->post('/entrar', ['email' => 'a@b.com', 'senha' => 'x'])->assertStatus(419);

        // Webhook sem cookies nem token CSRF: chega ao controller (401 por falta do asaas-access-token).
        $this->postJson('/webhooks/asaas', ['event' => 'PAYMENT_RECEIVED'])
            ->assertStatus(401)
            ->assertJson(['erro' => 'nao autorizado']);

        $this->postJson('/webhooks/asaas', ['id' => 'evt_1', 'event' => 'PAYMENT_UPDATED'], [
            'asaas-access-token' => config('services.asaas.webhook_token'),
        ])->assertOk()->assertJson(['recebido' => true]);
    }

    public function test_pagina_de_pagamento_abre_sem_sessao_e_sem_assets_do_painel(): void
    {
        Http::fake();
        $parcela = $this->criarParcela(['vencimento' => today()->addMonths(2)]);

        $resposta = $this->get("/p/{$parcela->token_pagamento}");

        $resposta->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $html = $resposta->getContent();
        $this->assertStringNotContainsString('/build/', $html);
        $this->assertStringNotContainsString('data-page="app"', $html);
        $this->assertStringNotContainsString('/entrar', $html);
    }

    public function test_toda_rota_fora_da_lista_publica_exige_sessao(): void
    {
        $semProtecao = collect(Route::getRoutes()->getRoutes())
            ->reject(fn ($rota) => in_array($rota->uri(), self::ROTAS_PUBLICAS, true))
            ->reject(fn ($rota) => in_array('auth', $rota->gatherMiddleware(), true))
            ->map(fn ($rota) => implode('|', $rota->methods()).' '.$rota->uri())
            ->values()
            ->all();

        $this->assertSame([], $semProtecao, 'Rotas sem o middleware auth: coloque-as no grupo protegido.');
    }

    public function test_devtools_do_inertia_mascara_a_senha(): void
    {
        $this->assertContains('senha', config('inertia.devtools.redact.keys'));
    }

    public function test_devtools_do_inertia_nao_existe_fora_do_ambiente_local(): void
    {
        // APP_ENV=testing, como produção: o Inertia nem registra as rotas.
        $this->getJson('/_inertia/devtools/entries')->assertNotFound();
    }
}

class CsrfLigadoNoTeste extends VerifyCsrfToken
{
    protected function runningUnitTests()
    {
        return false;
    }
}
