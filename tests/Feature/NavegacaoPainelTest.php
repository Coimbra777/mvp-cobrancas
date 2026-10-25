<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavegacaoPainelTest extends TestCase
{
    use RefreshDatabase;

    public static function areas(): array
    {
        return [
            ['/painel', 'Painel'],
            ['/clientes', 'Clientes'],
            ['/emprestimos', 'Empréstimos'],
            ['/parcelas', 'Parcelas'],
            ['/lembretes', 'Lembretes'],
            ['/configuracoes', 'Configurações'],
        ];
    }

    #[DataProvider('areas')]
    public function test_area_abre_a_pagina_provisoria_para_o_usuario(string $url, string $titulo): void
    {
        $this->actingAs(User::factory()->create())
            ->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('EmConstrucao')
                ->where('titulo', $titulo)
            );
    }

    #[DataProvider('areas')]
    public function test_area_exige_sessao(string $url, string $titulo): void
    {
        $this->get($url)->assertRedirect('/entrar');
    }

    public function test_raiz_leva_ao_painel(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/painel');
    }

    public function test_sobras_do_boilerplate_nao_existem(): void
    {
        $this->get('/contato')->assertNotFound();
    }
}
