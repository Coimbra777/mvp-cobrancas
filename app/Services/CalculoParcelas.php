<?php

namespace App\Services;

use App\Enums\MetodoCalculo;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class CalculoParcelas
{
    /**
     * Calcula o total e as parcelas de um empréstimo.
     *
     * - Total: simples = valor × (1 + i × n); composto = valor × (1 + i)^n, arredondado uma única vez.
     * - Parcelas iguais em centavos; a diferença do arredondamento vai para a última.
     * - Vencimentos: mesmo dia nos meses seguintes, sempre a partir do 1º vencimento;
     *   sem esse dia no mês, usa o último dia (31/01 → 28/02 → 31/03).
     *
     * @return array{total: int, parcelas: list<array{numero: int, valor_centavos: int, vencimento: CarbonInterface}>}
     */
    public function calcular(int $valorCentavos, float $taxaMensalPercentual, MetodoCalculo $metodo, int $quantidade, CarbonInterface $primeiroVencimento): array
    {
        if ($valorCentavos <= 0 || $quantidade < 1 || $taxaMensalPercentual < 0) {
            throw new InvalidArgumentException('Valor, taxa ou número de parcelas inválido.');
        }

        $taxa = $taxaMensalPercentual / 100;

        $fator = match ($metodo) {
            MetodoCalculo::Simples => 1 + $taxa * $quantidade,
            MetodoCalculo::Composto => (1 + $taxa) ** $quantidade,
        };

        $total = (int) round($valorCentavos * $fator);
        $base = intdiv($total, $quantidade);

        $parcelas = [];
        for ($i = 0; $i < $quantidade; $i++) {
            $parcelas[] = [
                'numero' => $i + 1,
                'valor_centavos' => $i === $quantidade - 1 ? $total - $base * ($quantidade - 1) : $base,
                'vencimento' => $primeiroVencimento->copy()->addMonthsNoOverflow($i),
            ];
        }

        return ['total' => $total, 'parcelas' => $parcelas];
    }
}
