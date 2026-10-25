<?php

namespace App\Enums;

enum MetodoCalculo: string
{
    // total = valor × (1 + taxa × parcelas)
    case Simples = 'simples';
    // total = valor × (1 + taxa) ^ parcelas
    case Composto = 'composto';

    public function rotulo(): string
    {
        return match ($this) {
            self::Simples => 'Juros simples',
            self::Composto => 'Juros compostos',
        };
    }
}
