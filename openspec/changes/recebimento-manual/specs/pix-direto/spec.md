# Spec Delta

## Purpose

Gera, sem intermediário, o código Pix copia e cola e o QR Code de uma parcela, com a chave Pix do banco do Dario e o valor da parcela, para o modo de recebimento manual.

## ADDED Requirements

### Requirement: Código Pix no padrão BR Code
O sistema SHALL gerar um código Pix estático no padrão BR Code do Banco Central (EMV), contendo a chave Pix configurada, o valor da parcela com 2 casas decimais, o nome do recebedor (até 25 caracteres), a cidade (até 15 caracteres), o identificador "***" e o CRC16 correto no fim.

#### Scenario: Código válido
- **WHEN** o código de uma parcela de R$ 443,66 é gerado com a chave "dario@exemplo.com", recebedor "DARIO SOUZA" e cidade "FORTALEZA"
- **THEN** o código começa com "000201", contém "BR.GOV.BCB.PIX", "dario@exemplo.com", "5406443.66", "5802BR", "5911DARIO SOUZA" e "6009FORTALEZA", e termina com "6304" seguido do CRC16 de 4 dígitos hexadecimais calculado sobre todo o código anterior

### Requirement: Nome e cidade normalizados
Nome do recebedor e cidade SHALL ser convertidos para letras sem acento, em maiúsculas, cortados nos limites do padrão e sem espaços nas pontas, para que os apps dos bancos aceitem o código.

#### Scenario: Acentos e tamanho
- **WHEN** o recebedor é "Dário de Souza Empréstimos Ltda" e a cidade é "São Gonçalo do Amarante"
- **THEN** o código usa "DARIO DE SOUZA EMPRESTIMO" (25 caracteres) e "SAO GONCALO DO" (15 caracteres cortados, sem o espaço final)
