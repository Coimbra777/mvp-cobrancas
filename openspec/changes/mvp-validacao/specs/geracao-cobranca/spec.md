# Spec Delta

## Purpose

Cria a cobrança Pix de cada parcela no Asaas como QR Code estático: pela rotina diária, para as parcelas que vencem em até 5 dias, e com o valor original para o dia corrente quando a página de uma parcela atrasada é aberta.

## ADDED Requirements

### Requirement: Rotina diária cria o QR Code das parcelas próximas do vencimento
Uma rotina diária SHALL criar um QR Code estático para cada parcela em aberto com vencimento de hoje até hoje + 5 dias que ainda não tenha QR Code vigente, guardando o id do QR Code e o payload. Rodar de novo MUST NOT duplicar.

#### Scenario: Parcela dentro da janela
- **WHEN** a rotina roda e há uma parcela em aberto vencendo em 2 dias sem QR Code
- **THEN** um QR Code é criado no Asaas e o id e o payload ficam guardados

#### Scenario: Segunda execução no mesmo dia
- **WHEN** a rotina roda de novo
- **THEN** nenhum QR Code novo é criado para essa parcela

#### Scenario: Falha recuperada no dia seguinte
- **WHEN** a criação falha hoje
- **THEN** a falha é registrada e a rotina de amanhã cria o QR Code enquanto a parcela estiver na janela

### Requirement: Asaas recebe só o valor da parcela
A criação do QR Code SHALL enviar a chave Pix configurada, o valor da parcela, a descrição "Parcela N de M", a expiração às 23:59:59 do vencimento, `allowsMultiplePayments` = false e o `externalReference` da cobrança, e MUST NOT enviar multa ou juros.

#### Scenario: Campos enviados
- **WHEN** o QR Code de uma parcela de R$ 1.050,00 que vence em 30/10/2026 é criado
- **THEN** o Asaas recebe valor 1050.00, expiração "2026-10-30 23:59:59", `allowsMultiplePayments` false e nenhum campo de multa ou juros

### Requirement: QR Code do dia para parcela atrasada
Ao abrir a página de pagamento de uma parcela atrasada, o sistema SHALL garantir um QR Code com o valor original da parcela que expira às 23:59:59 de hoje, reaproveitando o do dia se já existir e substituindo o anterior (remoção no Asaas e marcação como substituído).

#### Scenario: Primeira abertura do dia
- **WHEN** a página de uma parcela atrasada é aberta e o último QR Code expirou
- **THEN** um QR Code novo com o valor original é criado, válido até hoje às 23:59:59, e o anterior fica marcado como substituído

#### Scenario: Reabertura no mesmo dia
- **WHEN** a mesma página é aberta de novo no mesmo dia
- **THEN** o mesmo QR Code é reaproveitado

### Requirement: Proteção contra produção fora de produção
Com o ambiente diferente de produção, o sistema MUST recusar chamadas a uma URL do Asaas que não seja de sandbox.

#### Scenario: URL de produção em ambiente local
- **WHEN** o ambiente é local e a URL configurada é a de produção
- **THEN** nenhuma requisição é enviada
