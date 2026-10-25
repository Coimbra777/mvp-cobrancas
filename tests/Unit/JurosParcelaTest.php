<?php

namespace Tests\Unit;

use App\Models\Parcela;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JurosParcelaTest extends TestCase
{
    private function parcela(array $atributos = []): Parcela
    {
        return new Parcela($atributos + ['valor_centavos' => 105000, 'valor_pago_centavos' => 0, 'vencimento' => '2026-10-20']);
    }

    public function test_sem_juros_ate_o_vencimento(): void
    {
        $this->assertSame(105000, $this->parcela()->valorDevidoCentavos(Carbon::parse('2026-10-20 23:00')));
    }

    public function test_juros_pro_rata_por_dia_de_atraso(): void
    {
        // 1.050,00 × (2% / 30) × 5 dias = 3,50
        $this->assertSame(105350, $this->parcela()->valorDevidoCentavos(Carbon::parse('2026-10-25 08:00')));
    }

    public function test_juros_incidem_so_sobre_o_valor_em_aberto(): void
    {
        // 500,00 em aberto × (2% / 30) × 3 dias = 1,00
        $parcela = $this->parcela(['valor_centavos' => 100000, 'valor_pago_centavos' => 50000]);

        $this->assertSame(50100, $parcela->valorDevidoCentavos(Carbon::parse('2026-10-23')));
    }

    public function test_taxa_vem_da_configuracao(): void
    {
        config(['cobranca.taxa_juros_mensal' => 3]);

        // 1.050,00 × (3% / 30) × 10 dias = 10,50
        $this->assertSame(106050, $this->parcela()->valorDevidoCentavos(Carbon::parse('2026-10-30')));
    }
}
