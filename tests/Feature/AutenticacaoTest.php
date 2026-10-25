<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['email' => 'dario@example.com', 'password' => 'senha-forte-123']);

        // Rota protegida só para os testes de redirecionamento (as áreas reais vêm na casca do painel).
        Route::middleware(['web', 'auth'])->get('/_area-protegida', fn () => 'conteúdo protegido');
    }

    private function entrar(array $dados = [])
    {
        return $this->post('/entrar', $dados + ['email' => 'dario@example.com', 'senha' => 'senha-forte-123']);
    }

    public function test_tela_de_entrada_renderiza_a_pagina_inertia(): void
    {
        $this->get('/entrar')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/Entrar'));
    }

    public function test_credenciais_corretas_abrem_a_sessao_e_vao_para_o_painel(): void
    {
        $this->get('/entrar');
        $idAntes = session()->getId();

        $this->entrar()->assertRedirect('/painel');

        $this->assertAuthenticatedAs($this->usuario);
        $this->assertNotSame($idAntes, session()->getId());
    }

    public function test_volta_para_a_pagina_pedida_depois_de_entrar(): void
    {
        $this->get('/_area-protegida')->assertRedirect('/entrar');

        $this->entrar()->assertRedirect('/_area-protegida');
    }

    public function test_credenciais_erradas_mostram_mensagem_generica(): void
    {
        $this->from('/entrar')->entrar(['senha' => 'senha-errada'])
            ->assertRedirect('/entrar')
            ->assertSessionHasErrors(['credenciais' => 'E-mail ou senha incorretos.'])
            ->assertSessionDoesntHaveErrors(['email', 'senha']);

        $this->from('/entrar')->entrar(['email' => 'outro@example.com'])
            ->assertSessionHasErrors(['credenciais' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_campos_obrigatorios_e_email_invalido(): void
    {
        $this->from('/entrar')->post('/entrar', ['email' => '', 'senha' => ''])
            ->assertSessionHasErrors(['email' => 'Informe o e-mail.', 'senha' => 'Informe a senha.']);

        $this->from('/entrar')->post('/entrar', ['email' => 'nao-e-email', 'senha' => 'x'])
            ->assertSessionHasErrors(['email' => 'Informe um e-mail válido.']);

        $this->assertGuest();
    }

    public function test_sexta_tentativa_e_bloqueada_mesmo_com_a_senha_certa(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/entrar')->entrar(['senha' => 'senha-errada']);
        }

        $resposta = $this->from('/entrar')->entrar();

        $resposta->assertSessionHasErrors('credenciais');
        $this->assertMatchesRegularExpression(
            '/^Muitas tentativas\. Tente de novo em \d+ segundos\.$/',
            session('errors')->first('credenciais'),
        );
        $this->assertGuest();
    }

    public function test_sucesso_zera_o_contador_de_falhas(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->from('/entrar')->entrar(['senha' => 'senha-errada']);
        }
        $this->entrar()->assertRedirect('/painel');
        $this->post('/sair');

        // Se o contador não tivesse zerado, a 2ª falha abaixo já seria a 6ª.
        for ($i = 0; $i < 4; $i++) {
            $this->from('/entrar')->entrar(['senha' => 'senha-errada'])
                ->assertSessionHasErrors(['credenciais' => 'E-mail ou senha incorretos.']);
        }
    }

    public function test_manter_conectado_continua_autenticado_apos_a_sessao_expirar(): void
    {
        $resposta = $this->entrar(['manter_conectado' => true]);
        $nomeCookie = Auth::guard('web')->getRecallerName();
        $resposta->assertCookie($nomeCookie);
        $lembrar = $resposta->getCookie($nomeCookie, false)->getValue();

        // Sessão expirada: sem dados de sessão, só o cookie de "lembrar".
        $this->flushSession();
        Auth::forgetGuards();

        $this->withUnencryptedCookie($nomeCookie, $lembrar)
            ->get('/_area-protegida')
            ->assertOk()
            ->assertSee('conteúdo protegido');
    }

    public function test_sem_manter_conectado_a_sessao_expirada_volta_para_entrar(): void
    {
        $this->entrar()->assertCookieMissing(Auth::guard('web')->getRecallerName());

        $this->flushSession();
        Auth::forgetGuards();

        $this->get('/_area-protegida')->assertRedirect('/entrar');
    }

    public function test_sair_invalida_a_sessao_e_renova_o_token(): void
    {
        $this->entrar();
        $tokenAntes = session()->token();

        $this->post('/sair')->assertRedirect('/entrar');

        $this->assertGuest();
        $this->assertNotSame($tokenAntes, session()->token());
    }

    public function test_sair_por_get_nao_encerra_a_sessao(): void
    {
        $this->actingAs($this->usuario)->get('/sair')->assertStatus(405);

        $this->assertAuthenticatedAs($this->usuario);
    }

    public function test_usuario_autenticado_em_entrar_vai_para_o_painel(): void
    {
        $this->actingAs($this->usuario)->get('/entrar')->assertRedirect('/painel');
    }

    public function test_nao_existe_cadastro_publico_nem_recuperacao_de_senha(): void
    {
        foreach (['/register', '/cadastro', '/forgot-password'] as $rota) {
            $this->get($rota)->assertNotFound();
        }
    }

    public function test_nao_existe_api_publica(): void
    {
        $this->getJson('/api/user')->assertNotFound();
    }
}
