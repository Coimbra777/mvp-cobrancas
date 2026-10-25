<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefinirUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private function rodar(string $senha, ?string $confirmacao = null, array $opcoes = [])
    {
        return $this->artisan('caderneta:usuario', $opcoes + ['--nome' => 'Dario Souza', '--email' => 'dario@example.com'])
            ->expectsQuestion('Senha (mínimo 10 caracteres)', $senha)
            ->expectsQuestion('Confirme a senha', $confirmacao ?? $senha);
    }

    public function test_primeira_execucao_cria_o_usuario_com_senha_em_hash(): void
    {
        $this->rodar('senha-forte-123')->expectsOutput('Usuário criado.')->assertSuccessful();

        $usuario = User::sole();
        $this->assertSame('Dario Souza', $usuario->name);
        $this->assertSame('dario@example.com', $usuario->email);
        $this->assertNotSame('senha-forte-123', $usuario->password);
        $this->assertTrue(Hash::check('senha-forte-123', $usuario->password));
    }

    public function test_redefinicao_atualiza_o_mesmo_usuario(): void
    {
        $this->rodar('senha-forte-123')->assertSuccessful();
        $id = User::sole()->id;

        $this->rodar('outra-senha-456', null, ['--nome' => 'Dario', '--email' => 'dario@novo.com'])
            ->expectsOutput('Usuário atualizado.')
            ->assertSuccessful();

        $this->assertSame(1, User::count());
        $usuario = User::sole();
        $this->assertSame($id, $usuario->id);
        $this->assertSame('dario@novo.com', $usuario->email);
        $this->assertTrue(Hash::check('outra-senha-456', $usuario->password));
    }

    public function test_recusa_senha_curta_sem_alterar_nada(): void
    {
        $this->rodar('curta')
            ->expectsOutput('A senha precisa ter pelo menos 10 caracteres.')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_recusa_confirmacao_diferente(): void
    {
        $this->rodar('senha-forte-123', 'senha-forte-999')
            ->expectsOutput('As senhas não conferem.')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}
