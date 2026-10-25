<?php

namespace App\Enums;

enum ParcelaStatus: string
{
    case Aberta = 'aberta';
    case Paga = 'paga';
    case Cancelada = 'cancelada';
}
