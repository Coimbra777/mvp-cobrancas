<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaPropsCompartilhadasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.ensure_pages_exist' => false]);
        Route::middleware('web')->get('/_teste-props', fn () => Inertia::render('Teste'));
    }

    public function test_visitante_recebe_apenas_as_props_minimas(): void
    {
        $this->get('/_teste-props')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teste')
                ->where('app.nome', 'Caderneta')
                ->where('auth.usuario', null)
                ->where('flash.sucesso', null)
                ->where('flash.erro', null)
                ->has('errors')
                ->etc()
            );
    }

    public function test_usuario_logado_recebe_so_o_nome(): void
    {
        $usuario = User::factory()->create(['name' => 'Dario Souza', 'email' => 'dario@example.com']);

        $this->actingAs($usuario)
            ->withSession(['sucesso' => 'Salvo.'])
            ->get('/_teste-props')
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.usuario', ['nome' => 'Dario Souza'])
                ->where('flash.sucesso', 'Salvo.')
                ->etc()
            );
    }

    public function test_nao_ha_outras_props_compartilhadas(): void
    {
        $props = $this->get('/_teste-props')->viewData('page')['props'];

        $this->assertEqualsCanonicalizing(['errors', 'app', 'auth', 'flash'], array_keys($props));
    }
}
