# Spec Delta

## Purpose

Dá ao Dario a lista do que tem para receber e as duas ações do dia a dia: dar baixa numa parcela paga e mandar a cobrança pelo WhatsApp com o link de pagamento.

## ADDED Requirements

### Requirement: Parcelas em aberto com filtro
A página Cobranças SHALL listar as parcelas em aberto ordenadas por vencimento, com cliente, "Parcela N de M", vencimento, valor e situação, e SHALL oferecer o filtro "Atrasadas" (vencimento antes de hoje) e "A vencer" (vencimento hoje ou depois), com a contagem de cada um. A raiz `/` SHALL levar a esta página.

#### Scenario: Filtro de atrasadas
- **WHEN** há uma parcela vencida ontem e outra que vence amanhã, e o filtro "Atrasadas" está ativo
- **THEN** só a parcela vencida aparece, com a situação "Atrasada"

#### Scenario: Parcelas pagas não aparecem
- **WHEN** uma parcela está paga
- **THEN** ela não aparece em nenhum filtro da página Cobranças

### Requirement: Dar baixa
O botão "Dar baixa" SHALL, após confirmação, marcar a parcela em aberto como paga com a data de hoje, sem pagamento parcial, e mostrar "Baixa registrada.".

#### Scenario: Baixa manual
- **WHEN** o Dario confirma "Dar baixa" numa parcela em aberto
- **THEN** a parcela fica paga com a data de hoje e sai da lista de Cobranças

#### Scenario: Parcela já paga
- **WHEN** a baixa é pedida para uma parcela que já está paga
- **THEN** nada muda e o sistema avisa "Esta parcela já está paga."

### Requirement: Enviar cobrança no WhatsApp
O botão "Enviar no WhatsApp" SHALL abrir `https://wa.me/55<telefone>` com uma mensagem pronta contendo o primeiro nome do cliente, "parcela N de M", o valor, o vencimento e o link da página de pagamento `/p/{token}`.

#### Scenario: Link da cobrança
- **WHEN** o Dario toca "Enviar no WhatsApp" na parcela 3 de 10 de Maria (85999990000), de R$ 433,33, que vence em 30/10/2026
- **THEN** abre o link `https://wa.me/5585999990000?text=...` cuja mensagem cita "Maria", "parcela 3 de 10", "R$ 433,33", "30/10/2026" e o endereço `/p/{token}` da parcela
