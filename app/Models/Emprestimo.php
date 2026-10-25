<?php

namespace App\Models;

use App\Enums\MetodoCalculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emprestimo extends Model
{
    protected $table = 'emprestimos';

    protected $fillable = [
        'cliente_id',
        'valor_centavos',
        'taxa_mensal',
        'metodo',
        'quantidade_parcelas',
        'primeiro_vencimento',
        'total_centavos',
    ];

    protected $casts = [
        'valor_centavos' => 'integer',
        'taxa_mensal' => 'decimal:2',
        'metodo' => MetodoCalculo::class,
        'quantidade_parcelas' => 'integer',
        'primeiro_vencimento' => 'date',
        'total_centavos' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function parcelas(): HasMany
    {
        return $this->hasMany(Parcela::class)->orderBy('numero');
    }
}
