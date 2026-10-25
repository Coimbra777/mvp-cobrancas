<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class PaginasDeErroTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth'])->group(function () {
            Route::post('/_teste-419', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
            Route::get('/_teste-500', fn () => throw new RuntimeException('detalhe interno secreto'));
        });
    }

    public function test_pagina_inexistente_mostra_404_do_painel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/nao-existe')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page->component('Erro')->where('status', 404));
    }

    public function test_sessao_expirada_mostra_419_em_vez_de_tela_tecnica(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/_teste-419')
            ->assertStatus(419)
            ->assertInertia(fn (Assert $page) => $page->component('Erro')->where('status', 419));
    }

    public function test_erro_interno_em_producao_nao_mostra_detalhes(): void
    {
        config(['app.debug' => false]);

        $resposta = $this->actingAs(User::factory()->create())->get('/_teste-500');

        $resposta->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page->component('Erro')->where('status', 500))
            ->assertDontSee('detalhe interno secreto')
            ->assertDontSee('RuntimeException');
    }

    public function test_erro_interno_e_registrado_no_log(): void
    {
        config(['app.debug' => false]);
        Exceptions::fake();

        $this->actingAs(User::factory()->create())->get('/_teste-500')->assertStatus(500);

        Exceptions::assertReported(fn (RuntimeException $e) => $e->getMessage() === 'detalhe interno secreto');
    }

    public function test_erro_interno_com_debug_mantem_o_diagnostico(): void
    {
        config(['app.debug' => true]);

        $this->actingAs(User::factory()->create())
            ->get('/_teste-500')
            ->assertStatus(500)
            ->assertSee('detalhe interno secreto');
    }

    public function test_webhook_continua_respondendo_em_json(): void
    {
        $this->postJson('/webhooks/asaas', [])->assertStatus(401)->assertJson(['erro' => 'nao autorizado']);
        $this->postJson('/webhooks/inexistente', [])->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_pagina_publica_inexistente_nao_usa_a_pagina_do_painel(): void
    {
        $resposta = $this->get('/p/token-que-nao-existe')->assertNotFound();

        $this->assertStringNotContainsString('data-page="app"', $resposta->getContent());
    }
}
