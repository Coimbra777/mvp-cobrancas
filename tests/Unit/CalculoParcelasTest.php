<?php

namespace Tests\Unit;

use App\Enums\MetodoCalculo;
use App\Services\CalculoParcelas;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculoParcelasTest extends TestCase
{
    private function calcular(int $valor, float $taxa, MetodoCalculo $metodo, int $n, string $primeiro = '2026-11-10'): array
    {
        return (new CalculoParcelas())->calcular($valor, $taxa, $metodo, $n, Carbon::parse($primeiro));
    }

    private function valores(array $resultado): array
    {
        return array_column($resultado['parcelas'], 'valor_centavos');
    }

    private function vencimentos(array $resultado): array
    {
        return array_map(fn ($p) => $p['vencimento']->toDateString(), $resultado['parcelas']);
    }

    public function test_juros_simples(): void
    {
        // R$ 1.000,00 a 10% a.m. em 3x: 1.000 × (1 + 0,10 × 3) = 1.300,00
        $resultado = $this->calcular(100000, 10, MetodoCalculo::Simples, 3);

        $this->assertSame(130000, $resultado['total']);
        $this->assertSame([43333, 43333, 43334], $this->valores($resultado));
    }

    public function test_juros_compostos(): void
    {
        // 1.000 × 1,1³ = 1.331,00
        $resultado = $this->calcular(100000, 10, MetodoCalculo::Composto, 3);

        $this->assertSame(133100, $resultado['total']);
        $this->assertSame([44366, 44366, 44368], $this->valores($resultado));
    }

    public function test_taxa_zero_devolve_o_valor_emprestado(): void
    {
        foreach (MetodoCalculo::cases() as $metodo) {
            $resultado = $this->calcular(100000, 0, $metodo, 4);

            $this->assertSame(100000, $resultado['total']);
            $this->assertSame([25000, 25000, 25000, 25000], $this->valores($resultado));
        }
    }

    public function test_total_arredondado_uma_vez_e_soma_das_parcelas_igual_ao_total(): void
    {
        // 1.500,50 a 2,5% composto em 7x: 150050 × 1,025^7 = 178362,29... → 178362
        $resultado = $this->calcular(150050, 2.5, MetodoCalculo::Composto, 7);

        $this->assertSame(178362, $resultado['total']);
        $this->assertSame($resultado['total'], array_sum($this->valores($resultado)));
        $this->assertSame([25480, 25480, 25480, 25480, 25480, 25480, 25482], $this->valores($resultado));
    }

    public function test_parcela_unica(): void
    {
        $resultado = $this->calcular(50000, 10, MetodoCalculo::Simples, 1);

        $this->assertSame([55000], $this->valores($resultado));
        $this->assertSame(['2026-11-10'], $this->vencimentos($resultado));
    }

    public function test_vencimentos_no_mesmo_dia_dos_meses_seguintes(): void
    {
        $resultado = $this->calcular(100000, 5, MetodoCalculo::Simples, 3, '2026-11-10');

        $this->assertSame(['2026-11-10', '2026-12-10', '2027-01-10'], $this->vencimentos($resultado));
        $this->assertSame([1, 2, 3], array_column($resultado['parcelas'], 'numero'));
    }

    public function test_dia_31_sem_estouro_e_volta_ao_31(): void
    {
        $resultado = $this->calcular(100000, 5, MetodoCalculo::Simples, 4, '2026-01-31');

        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'], $this->vencimentos($resultado));
    }

    public function test_dia_29_em_ano_bissexto_e_normal(): void
    {
        $this->assertSame(['2028-01-29', '2028-02-29'], $this->vencimentos($this->calcular(100000, 5, MetodoCalculo::Simples, 2, '2028-01-29')));
        $this->assertSame(['2027-01-29', '2027-02-28', '2027-03-29'], $this->vencimentos($this->calcular(100000, 5, MetodoCalculo::Simples, 3, '2027-01-29')));
    }

    public function test_recusa_dados_invalidos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calcular(0, 5, MetodoCalculo::Simples, 3);
    }
}
