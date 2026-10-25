<?php

namespace App\Models;

use App\Enums\CobrancaPixStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CobrancaPix extends Model
{
    protected $table = 'cobrancas_pix';

    protected $fillable = [
        'parcela_id',
        'status',
        'valor_centavos',
        'expira_em',
        'asaas_qrcode_id',
        'payload',
        'erro',
        'asaas_payment_id',
        'pago_em',
        'valor_pago_centavos',
        'valor_liquido_centavos',
    ];

    protected $casts = [
        'status' => CobrancaPixStatus::class,
        'expira_em' => 'datetime',
        'pago_em' => 'datetime',
        'valor_centavos' => 'integer',
        'valor_pago_centavos' => 'integer',
        'valor_liquido_centavos' => 'integer',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function estaVigente(CarbonInterface $agora): bool
    {
        return $this->status === CobrancaPixStatus::Ativa && $this->expira_em->gt($agora);
    }

    /**
     * Registra o pagamento Pix vindo do Asaas e dá baixa na parcela.
     * Idempotente pelo id do pagamento: CONFIRMED e RECEIVED do mesmo Pix baixam uma vez.
     *
     * @return string resultado: baixa | ja_baixada | outro_pagamento | parcela_ja_paga
     */
    public function registrarPagamento(array $payment): string
    {
        return DB::transaction(function () use ($payment) {
            $cobranca = self::lockForUpdate()->findOrFail($this->id);

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

            if ($cobranca->status === CobrancaPixStatus::Substituida) {
                Log::warning('Pix recebido de QR Code substituído', ['cobranca_pix_id' => $cobranca->id]);
            }

            $pagoEm = self::dataDoPagamento($payment);

            $cobranca->update([
                'status' => CobrancaPixStatus::Paga,
                'asaas_payment_id' => $payment['id'],
                'pago_em' => $pagoEm,
                'valor_pago_centavos' => (int) round($payment['value'] * 100),
                'valor_liquido_centavos' => isset($payment['netValue']) ? (int) round($payment['netValue'] * 100) : null,
            ]);

            $parcela = Parcela::lockForUpdate()->findOrFail($cobranca->parcela_id);

            if (! $parcela->darBaixa($pagoEm)) {
                // Ex.: baixa manual antes do Pix. O dinheiro entrou de novo; não paga a parcela duas vezes.
                Log::warning('Pix recebido para parcela já paga', ['parcela_id' => $parcela->id, 'cobranca_pix_id' => $cobranca->id]);

                return 'parcela_ja_paga';
            }

            return 'baixa';
        });
    }

    private static function dataDoPagamento(array $payment): Carbon
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
