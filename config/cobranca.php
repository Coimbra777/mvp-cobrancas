<?php

return [

    // Juros de atraso em % ao mês, aplicado pró-rata (taxa / 30 por dia de atraso).
    'taxa_juros_mensal' => (float) env('COBRANCA_TAXA_JUROS_MENSAL', 2),

    // Quantos dias antes do vencimento o QR Code da parcela em dia é criado.
    'dias_antecedencia' => 5,

];
