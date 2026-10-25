<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class DefinirUsuario extends Command
{
    protected $signature = 'caderneta:usuario
        {--nome= : nome exibido no painel}
        {--email= : e-mail usado para entrar}';

    protected $description = 'Cria ou redefine o único usuário da Caderneta (nome, e-mail e senha)';

    public function handle(): int
    {
        // Sistema de usuário único: sempre a mesma linha, nunca uma segunda.
        $usuario = User::query()->orderBy('id')->first() ?? new User();

        $dados = [
            'nome' => $this->option('nome') ?: $this->ask('Nome', $usuario->name),
            'email' => $this->option('email') ?: $this->ask('E-mail', $usuario->email),
            'senha' => $this->secret('Senha (mínimo 10 caracteres)'),
            'senha_confirmation' => $this->secret('Confirme a senha'),
        ];

        $validador = Validator::make($dados, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'senha' => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'nome.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'senha.required' => 'Informe a senha.',
            'senha.min' => 'A senha precisa ter pelo menos 10 caracteres.',
            'senha.confirmed' => 'As senhas não conferem.',
        ]);

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $mensagem) {
                $this->error($mensagem);
            }

            return self::FAILURE;
        }

        $criado = ! $usuario->exists;

        $usuario->fill([
            'name' => $dados['nome'],
            'email' => $dados['email'],
            'password' => $dados['senha'], // cast "hashed" grava só o hash
        ])->save();

        $this->info($criado ? 'Usuário criado.' : 'Usuário atualizado.');

        return self::SUCCESS;
    }
}
