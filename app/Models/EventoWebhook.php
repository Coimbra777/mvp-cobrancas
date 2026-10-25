<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoWebhook extends Model
{
    protected $table = 'eventos_webhook';

    protected $fillable = [
        'chave',
        'evento',
        'payload',
        'resultado',
        'processado_em',
    ];

    protected $casts = [
        'payload' => 'array',
        'processado_em' => 'datetime',
    ];
}
