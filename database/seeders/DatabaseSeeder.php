<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria da Silva', 'telefone' => '85999990000']);

        $emprestimo = $cliente->emprestimos()->create([
            'valor_centavos' => 300000,
            'quantidade_parcelas' => 3,
        ]);

        $parcelas = [
            ['numero' => 1, 'valor_centavos' => 105000, 'vencimento' => today()->subDays(3)], // atrasada
            ['numero' => 2, 'valor_centavos' => 105000, 'vencimento' => today()],             // vence hoje
            ['numero' => 3, 'valor_centavos' => 105000, 'vencimento' => today()->addDays(5)], // vence em 5 dias
        ];

        foreach ($parcelas as $dados) {
            $parcela = $emprestimo->parcelas()->create($dados);
            $this->command?->info("Parcela {$parcela->id} ({$parcela->vencimento->format('d/m/Y')}): ".route('pagamento.show', $parcela->token_pagamento));
        }
    }
}
