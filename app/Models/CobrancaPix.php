<?php

namespace App\Models;

use App\Enums\CobrancaPixStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'diferenca_centavos',
    ];

    protected $casts = [
        'status' => CobrancaPixStatus::class,
        'expira_em' => 'datetime',
        'pago_em' => 'datetime',
        'valor_centavos' => 'integer',
        'valor_pago_centavos' => 'integer',
        'valor_liquido_centavos' => 'integer',
        'diferenca_centavos' => 'integer',
    ];

    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcela::class);
    }

    public function estaVigente(CarbonInterface $agora): bool
    {
        return $this->status === CobrancaPixStatus::Ativa && $this->expira_em->gt($agora);
    }
}
