<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoWebhook extends Model
{
    protected $table = 'eventos_webhook';

    protected $fillable = [
        'chave',
        'evento',
        'asaas_payment_id',
        'pix_qrcode_id',
        'payload',
        'resultado',
        'erro',
        'processado_em',
    ];

    protected $casts = [
        'payload' => 'array',
        'processado_em' => 'datetime',
    ];
}
