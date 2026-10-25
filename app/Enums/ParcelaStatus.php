<?php

namespace App\Enums;

// "Atrasada" e "a vencer" são calculadas pelo vencimento; não são status gravados.
enum ParcelaStatus: string
{
    case Aberta = 'aberta';
    case Paga = 'paga';
}
