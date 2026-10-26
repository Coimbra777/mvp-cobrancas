# Spec Delta

## Purpose

No modo manual, liga o pagamento feito pelo cliente à baixa dada pelo Dario: o cliente envia o comprovante do Pix pela página de pagamento e o Dario confere numa tela própria, dando baixa ou recusando.

## ADDED Requirements

### Requirement: Cliente envia o comprovante
No modo manual, a página de pagamento de uma parcela em aberto SHALL permitir enviar um comprovante (JPG, PNG, WEBP ou PDF, até 5 MB). O arquivo MUST ficar fora da área pública e só SHALL ser acessível ao usuário autenticado.

#### Scenario: Envio válido
- **WHEN** o cliente envia um PDF de 300 KB na página da parcela
- **THEN** o comprovante fica registrado como pendente e a página mostra "Comprovante enviado, aguardando confirmação"

#### Scenario: Arquivo inválido
- **WHEN** o cliente envia um arquivo de 8 MB ou um `.exe`
- **THEN** nada é registrado e a página explica o formato e o tamanho aceitos

#### Scenario: Arquivo não é público
- **WHEN** alguém sem sessão tenta abrir o endereço do arquivo de um comprovante
- **THEN** o sistema redireciona para `/entrar`

### Requirement: Limite de envios
Uma parcela SHALL aceitar no máximo 3 comprovantes pendentes ao mesmo tempo, e o envio SHALL ser limitado a 5 por hora por endereço, para evitar abuso da página pública.

#### Scenario: Quarto comprovante pendente
- **WHEN** a parcela já tem 3 comprovantes pendentes e chega mais um
- **THEN** o envio é recusado com a mensagem "Já recebemos seus comprovantes. Aguarde a confirmação."

### Requirement: Dario revisa os comprovantes
A tela "Comprovantes" SHALL listar os comprovantes pendentes, do mais antigo para o mais novo, com cliente, "Parcela N de M", valor, vencimento, data do envio e um link para abrir o arquivo, e SHALL oferecer "Dar baixa" e "Recusar".

#### Scenario: Dar baixa pelo comprovante
- **WHEN** o Dario clica em "Dar baixa" num comprovante pendente
- **THEN** a parcela fica paga com a data de hoje, o comprovante fica aprovado, os outros comprovantes pendentes da mesma parcela ficam recusados e o comprovante sai da lista

#### Scenario: Recusar
- **WHEN** o Dario clica em "Recusar"
- **THEN** o comprovante fica recusado, a parcela continua em aberto e a página do cliente mostra "Comprovante recusado. Confira o valor e envie outro."

### Requirement: Parcela paga não recebe comprovante
A página de uma parcela paga MUST NOT oferecer envio de comprovante, e um envio para ela SHALL ser recusado.

#### Scenario: Envio depois da baixa
- **WHEN** chega um comprovante para uma parcela já paga
- **THEN** nada é registrado e a página mostra "Paga"
