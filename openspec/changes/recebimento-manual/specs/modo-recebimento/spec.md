# Spec Delta

## Purpose

Permite operar a Caderneta com ou sem o Asaas: no modo manual o Dario confere comprovantes de Pix feitos direto na conta dele; no modo asaas a cobrança e a baixa são automáticas.

## ADDED Requirements

### Requirement: Um modo de recebimento por vez
O sistema SHALL operar em exatamente um modo de recebimento, definido na configuração do ambiente: `manual` (padrão) ou `asaas`. Um valor diferente desses MUST impedir o sistema de subir, com uma mensagem que explique os valores aceitos.

#### Scenario: Sem configuração
- **WHEN** o modo não foi configurado
- **THEN** o sistema opera no modo manual

#### Scenario: Valor inválido
- **WHEN** o modo configurado é "pix"
- **THEN** o sistema não sobe e a mensagem informa que os valores aceitos são "manual" e "asaas"

### Requirement: Modo manual não usa o Asaas
No modo manual, o sistema MUST NOT fazer nenhuma chamada ao Asaas: a rotina de geração não cria QR Codes, o webhook responde 404 e a página de pagamento usa o Pix direto.

#### Scenario: Manual sem chaves do Asaas
- **WHEN** o modo é manual e as chaves do Asaas estão vazias
- **THEN** a página de pagamento, a rotina diária e as telas do painel funcionam sem erro

### Requirement: Configuração obrigatória por modo
O modo manual SHALL exigir a chave Pix, o nome do recebedor e a cidade; o modo asaas SHALL exigir a URL, a chave de API, a chave Pix e o token do webhook do Asaas. Faltando um item, a página de pagamento SHALL mostrar o aviso de falha do Pix e o motivo SHALL ir para o log.

#### Scenario: Modo manual sem chave Pix
- **WHEN** o modo é manual e a chave Pix do Dario está vazia
- **THEN** a página de pagamento abre com o aviso "Não conseguimos gerar o Pix agora. Tente de novo em alguns minutos." e o log registra que falta a chave Pix
