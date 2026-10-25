<?php

namespace App\Services;

use App\Enums\CobrancaPixStatus;
use App\Enums\ParcelaStatus;
use App\Models\CobrancaPix;
use App\Models\Parcela;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BaixaPixService
{
    /**
     * Dá baixa na parcela a partir de uma cobrança (payment) do Asaas.
     * Idempotente: PAYMENT_CONFIRMED e PAYMENT_RECEIVED do mesmo Pix baixam uma vez só.
     *
     * @return string resultado: baixa | ja_baixada | outro_pagamento | parcela_ja_paga
     */
    public function executar(CobrancaPix $cobranca, array $payment): string
    {
        return DB::transaction(function () use ($cobranca, $payment) {
            $cobranca = CobrancaPix::lockForUpdate()->findOrFail($cobranca->id);

            if ($cobranca->asaas_payment_id) {
                if ($cobranca->asaas_payment_id === $payment['id']) {
                    return 'ja_baixada';
                }

                Log::error('QR Code com mais de um pagamento no Asaas', [
                    'cobranca_pix_id' => $cobranca->id,
                    'registrado' => $cobranca->asaas_payment_id,
                    'novo' => $payment['id'],
                ]);

                return 'outro_pagamento';
            }

            $parcela = Parcela::lockForUpdate()->findOrFail($cobranca->parcela_id);
            $estavaSubstituida = $cobranca->status === CobrancaPixStatus::Substituida;

            $valor = (int) round($payment['value'] * 100);
            $liquido = isset($payment['netValue']) ? (int) round($payment['netValue'] * 100) : null;
            $pagoEm = $this->dataPagamento($payment);

            if ($parcela->status === ParcelaStatus::Paga) {
                // Dinheiro entrou de novo (ex.: baixa manual antes do Pix). Registra e alerta.
                $resultado = 'parcela_ja_paga';
                $diferenca = $valor;
                $parcela->increment('valor_pago_centavos', $valor);
            } else {
                $resultado = 'baixa';
                $diferenca = $valor - $parcela->valorDevidoCentavos($pagoEm);
                $parcela->registrarPagamento($valor, $pagoEm, quitar: true);
            }

            $cobranca->update([
                'status' => CobrancaPixStatus::Paga,
                'asaas_payment_id' => $payment['id'],
                'pago_em' => $pagoEm,
                'valor_pago_centavos' => $valor,
                'valor_liquido_centavos' => $liquido,
                'diferenca_centavos' => $diferenca,
            ]);

            if ($estavaSubstituida || $diferenca !== 0) {
                Log::warning('Pix recebido com divergência', [
                    'resultado' => $resultado,
                    'parcela_id' => $parcela->id,
                    'cobranca_pix_id' => $cobranca->id,
                    'qrcode_substituido' => $estavaSubstituida,
                    'valor_pago_centavos' => $valor,
                    'diferenca_centavos' => $diferenca,
                ]);
            }

            return $resultado;
        });
    }

    private function dataPagamento(array $payment): Carbon
    {
        $data = $payment['clientPaymentDate'] ?? $payment['paymentDate'] ?? $payment['confirmedDate'] ?? null;

        if (! $data) {
            return now();
        }

        // O Asaas manda só a data; se for hoje, guarda a hora em que a baixa chegou.
        $pagoEm = Carbon::parse($data);

        return $pagoEm->isToday() ? now() : $pagoEm;
    }
}
