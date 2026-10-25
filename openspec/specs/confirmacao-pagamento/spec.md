# confirmacao-pagamento Specification

## Purpose
Confirma automaticamente o pagamento Pix pelo webhook do Asaas, processando cada evento uma vez e dando baixa na parcela certa.

## Requirements

### Requirement: Autenticação do webhook
`POST /webhooks/asaas` SHALL aceitar apenas requisições com `asaas-access-token` igual ao token configurado; nos demais casos SHALL responder 401 sem gravar nada.

#### Scenario: Token inválido ou ausente
- **WHEN** chega uma requisição sem o cabeçalho ou com valor errado
- **THEN** o sistema responde 401 e nenhum evento é gravado

### Requirement: Evento processado uma vez
O sistema SHALL gravar cada evento antes de processá-lo, com o id do evento como chave única, e responder 200 sem reprocessar um evento repetido.

#### Scenario: Evento duplicado
- **WHEN** o mesmo evento chega duas vezes
- **THEN** existe um único registro do evento e a parcela recebe uma única baixa

### Requirement: Baixa automática pelo QR Code
Para `PAYMENT_CONFIRMED` e `PAYMENT_RECEIVED`, o sistema SHALL encontrar a cobrança pelo `pixQrCodeId` (ou, como reserva, consultando o pagamento na API do Asaas) e dar baixa: a parcela fica paga e a cobrança guarda `value` como valor recebido, `netValue` como valor líquido, a data e o id do pagamento.

#### Scenario: Pagamento confirmado
- **WHEN** chega `PAYMENT_RECEIVED` com o `pixQrCodeId` da cobrança de uma parcela, `value` 1050.00 e `netValue` 1049.01
- **THEN** essa parcela fica paga, a cobrança guarda 105000 e 104901 centavos, e as outras parcelas não mudam

#### Scenario: Evento sem pixQrCodeId
- **WHEN** o evento não traz `pixQrCodeId` e a consulta do pagamento no Asaas retorna o de uma cobrança
- **THEN** a parcela dessa cobrança recebe a baixa

### Requirement: Baixa idempotente por pagamento
O sistema SHALL dar no máximo uma baixa por id de pagamento do Asaas, para que `PAYMENT_CONFIRMED` e `PAYMENT_RECEIVED` do mesmo Pix baixem uma vez só. Se a parcela já estiver paga (por exemplo, por baixa manual), o pagamento SHALL ser registrado na cobrança sem alterar a parcela, com um aviso no log.

#### Scenario: CONFIRMED seguido de RECEIVED
- **WHEN** chegam `PAYMENT_CONFIRMED` e depois `PAYMENT_RECEIVED` do mesmo pagamento
- **THEN** a parcela recebe uma única baixa

### Requirement: Falha é reenviada pelo Asaas
Se o processamento falhar, o sistema SHALL responder 500 e registrar a falha no log, mantendo o evento como não processado para que o reenvio do Asaas o processe. Eventos que não são de pagamento SHALL ser só gravados.

#### Scenario: Erro e reenvio
- **WHEN** o processamento de um evento falha e o Asaas reenvia o mesmo evento
- **THEN** a primeira resposta é 500 e o reenvio processa o evento e responde 200
