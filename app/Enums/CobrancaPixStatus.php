<?php

namespace App\Enums;

enum CobrancaPixStatus: string
{
    // Intenção gravada antes de chamar o Asaas
    case Pendente = 'pendente';
    case Ativa = 'ativa';
    case Falhou = 'falhou';
    // Trocada por outro QR Code (valor mudou ou novo dia de atraso)
    case Substituida = 'substituida';
    case Paga = 'paga';
}
