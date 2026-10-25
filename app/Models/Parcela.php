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
        'valor_pago_centavos',
        'vencimento',
        'status',
        'pago_em',
    ];

    protected $casts = [
        'vencimento' => 'date',
        'pago_em' => 'datetime',
        'status' => ParcelaStatus::class,
        'valor_centavos' => 'integer',
        'valor_pago_centavos' => 'integer',
    ];

    protected $attributes = [
        'valor_pago_centavos' => 0,
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

    public function valorEmAbertoCentavos(): int
    {
        return max(0, $this->valor_centavos - $this->valor_pago_centavos);
    }

    // Valor em aberto × (taxa mensal / 30) × dias de atraso, arredondado em centavos.
    public function jurosCentavos(CarbonInterface $dia): int
    {
        $taxaDiaria = config('cobranca.taxa_juros_mensal') / 100 / 30;

        return (int) round($this->valorEmAbertoCentavos() * $taxaDiaria * $this->diasAtraso($dia));
    }

    public function valorDevidoCentavos(CarbonInterface $dia): int
    {
        return $this->valorEmAbertoCentavos() + $this->jurosCentavos($dia);
    }

    // Usado pela baixa automática (Pix) e pela baixa manual (dinheiro, parcial).
    public function registrarPagamento(int $centavos, CarbonInterface $em, bool $quitar = false): void
    {
        $devido = $this->valorDevidoCentavos($em);

        $this->valor_pago_centavos += $centavos;

        if ($quitar || $centavos >= $devido) {
            $this->status = ParcelaStatus::Paga;
            $this->pago_em = $em;
        }

        $this->save();
    }
}
