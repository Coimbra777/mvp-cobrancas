<?php

namespace App\Models;

use App\Enums\ParcelaStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Parcela extends Model
{
    protected $table = 'parcelas';

    protected $fillable = [
        'emprestimo_id',
        'numero',
        'valor_centavos',
        'vencimento',
        'status',
        'pago_em',
    ];

    protected $casts = [
        'vencimento' => 'date',
        'pago_em' => 'datetime',
        'status' => ParcelaStatus::class,
        'valor_centavos' => 'integer',
    ];

    protected $attributes = [
        'status' => 'aberta',
    ];

    protected static function booted(): void
    {
        static::creating(function (Parcela $parcela) {
            $parcela->token_pagamento ??= Str::random(48);
        });
    }

    public function emprestimo(): BelongsTo
    {
        return $this->belongsTo(Emprestimo::class);
    }

    public function cobrancasPix(): HasMany
    {
        return $this->hasMany(CobrancaPix::class);
    }

    public function descricao(): string
    {
        return "Parcela {$this->numero} de {$this->emprestimo->quantidade_parcelas}";
    }

    public function estaAberta(): bool
    {
        return $this->status === ParcelaStatus::Aberta;
    }

    public function diasAtraso(CarbonInterface $dia): int
    {
        $dia = $dia->copy()->startOfDay();

        return $dia->gt($this->vencimento) ? (int) $this->vencimento->diffInDays($dia) : 0;
    }

    /**
     * Marca a parcela como paga (baixa manual ou Pix). Sem pagamento parcial.
     * Retorna false se ela já estava paga.
     */
    public function darBaixa(?CarbonInterface $em = null): bool
    {
        if (! $this->estaAberta()) {
            return false;
        }

        $this->update(['status' => ParcelaStatus::Paga, 'pago_em' => $em ?? now()]);

        return true;
    }

    public function urlPagamento(): string
    {
        return route('pagamento.show', $this->token_pagamento);
    }

    /** Link wa.me com a mensagem de cobrança pronta (o Dario envia do próprio WhatsApp). */
    public function linkWhatsApp(): string
    {
        $cliente = $this->emprestimo->cliente;
        $valor = 'R$ '.number_format($this->valor_centavos / 100, 2, ',', '.');
        $verbo = $this->vencimento->lt(today()) ? 'venceu' : 'vence';

        $mensagem = "Olá, {$cliente->primeiroNome()}! Lembrete da parcela {$this->numero} de "
            ."{$this->emprestimo->quantidade_parcelas}: {$valor}, {$verbo} em {$this->vencimento->format('d/m/Y')}. "
            ."Para pagar por Pix, acesse: {$this->urlPagamento()}";

        return "https://wa.me/55{$cliente->telefone}?text=".rawurlencode($mensagem);
    }
}
