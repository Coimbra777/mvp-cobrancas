<?php

namespace Tests;

use App\Enums\CobrancaPixStatus;
use App\Models\Cliente;
use App\Models\CobrancaPix;
use App\Models\Parcela;
use Illuminate\Support\Facades\Http;

trait CriaParcelas
{
    protected function criarParcela(array $atributos = []): Parcela
    {
        $emprestimo = Cliente::create(['nome' => 'Maria da Silva', 'telefone' => '85999990000'])
            ->emprestimos()->create(['valor_centavos' => 1000000, 'quantidade_parcelas' => 10]);

        return $emprestimo->parcelas()->create($atributos + [
            'numero' => 3,
            'valor_centavos' => 105000,
            'vencimento' => today()->addDays(5),
        ]);
    }

    protected function criarCobranca(Parcela $parcela, string $qrCodeId, array $atributos = []): CobrancaPix
    {
        return $parcela->cobrancasPix()->create($atributos + [
            'status' => CobrancaPixStatus::Ativa,
            'valor_centavos' => $parcela->valor_centavos,
            'expira_em' => $parcela->vencimento->copy()->endOfDay(),
            'asaas_qrcode_id' => $qrCodeId,
            'payload' => "00020126PAYLOAD{$qrCodeId}",
        ]);
    }

    protected function fakeAsaas(array $qrCodeIds = ['qr_1', 'qr_2', 'qr_3']): void
    {
        $respostas = array_map(fn ($id) => ['id' => $id, 'payload' => "00020126PAYLOAD{$id}", 'allowsMultiplePayments' => false], $qrCodeIds);

        Http::fake([
            '*/pix/qrCodes/static/*' => Http::response(['deleted' => true]),
            '*/pix/qrCodes/static' => Http::sequence($respostas),
            '*/payments?*' => Http::response(['data' => []]),
        ]);
    }
}
