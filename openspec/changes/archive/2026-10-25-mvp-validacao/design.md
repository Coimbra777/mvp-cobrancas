# Design

## Context

Ver `proposal.md`. Ponto de partida: base arquivada (`base-arquitetura`: Laravel 13, Inertia 3 + Vue 3, login, casca do painel, tokens) e Fase 2 (QR Code estático, webhook, `/p/{token}`). Não há produção nem dados reais.

## Goals / Non-Goals

**Goals:** o menor código que cubra os 5 itens do escopo, mantendo a Fase 2 funcionando e testada onde tem regra (cálculo e webhook).

**Non-Goals:** reorganizar a Fase 2 além do necessário para tirar juros, conciliação, alertas e "diferença".

## Decisions

### D1. Migrations reescritas, sem migrations de alteração
Sem produção, as migrations de 2026-10-25 são editadas no lugar.

| Tabela | Campos |
|---|---|
| `clientes` | `nome`, `telefone` (só dígitos) |
| `emprestimos` | `cliente_id`, `valor_centavos`, `taxa_mensal` (decimal 5,2, em %), `metodo` (`simples` \| `composto`), `quantidade_parcelas`, `primeiro_vencimento`, `total_centavos` |
| `parcelas` | `emprestimo_id`, `numero`, `valor_centavos`, `vencimento`, `status` (`aberta` \| `paga`), `pago_em`, `token_pagamento` |
| `cobrancas_pix` | igual, sem `diferenca_centavos` |
| `eventos_webhook` | `chave`, `evento`, `payload`, `resultado`, `processado_em` |

### D2. Cálculo no service `CalculoParcelas`
- **Entrada e saída:** `calcular(valorCentavos, taxaPercentual, metodo, quantidade, primeiroVencimento)` devolve o total e a lista de parcelas (número, valor, vencimento).
- **Arredondamento:** o total é arredondado uma vez (`round`, meia unidade para cima); a parcela base é `intdiv(total, n)` e a última recebe `total - base × (n - 1)`.
- **Vencimentos:** `primeiro->copy()->addMonthsNoOverflow(i)`, sempre a partir do primeiro (nunca encadeado). Assim o dia 31 volta a ser 31 depois de fevereiro.
- A gravação do empréstimo e das parcelas fica numa transação no controller. Não há chamada externa nesse ponto.

### D3. Entrada de dinheiro e taxa em formato brasileiro
- O `FormRequest` normaliza "1.500,50", "1500,50" e "1500.50" no `prepareForValidation`.
- O valor é convertido para centavos por manipulação de string, sem float.
- Na taxa, a vírgula vira ponto e o valor é validado como decimal de 0 a 100 com até 2 casas.

### D4. Baixa unificada no model
- **Baixa manual:** `Parcela::darBaixa()` marca `status = paga` e `pago_em = now()`. Retorna `false` se a parcela já estava paga.
- **Baixa Pix:** `CobrancaPix::registrarPagamento(array $payment)` faz o seguinte, dentro de uma transação com lock:
  - ignora se o `asaas_payment_id` já está registrado;
  - grava `value`, `netValue`, a data e o id do pagamento;
  - chama `darBaixa()`; se a parcela já estava paga, só registra um aviso no log.
- **Services:** o `BaixaPixService` some. O `WebhookAsaasService` fica, por ser do Asaas, que é o que a regra de services permite, e enxuto: só `pixQrCodeId` com a consulta da API como reserva. Saem `externalReference`, o caminho por `paymentId` e a lista de alertas.

### D5. Webhook responde 500 em falha
- O controller grava o evento e chama o service.
- Se der exceção, registra no log e responde 500. O evento fica com `processado_em` nulo, e o reenvio do Asaas o reprocessa (`firstOrCreate` encontra o evento não processado).
- Isso substitui o comando `webhook:reprocessar`.

### D6. Parcela atrasada sem juros
- O `CobrancaPixService` usa sempre `valor_centavos`.
- Para parcela atrasada, a expiração é o fim de hoje e o QR Code do dia anterior é substituído.
- A "conciliação" (consulta de pagamentos do QR Code antigo) sai.
- A janela de 5 dias vira constante no service.

### D7. WhatsApp pelo model
- `Parcela::linkWhatsApp()` monta `https://wa.me/55{telefone}?text=` com a mensagem codificada por `rawurlencode`.
- A mensagem: "Olá, {primeiro nome}! Lembrete da parcela {n} de {m}: {valor}, {vence/venceu} em {data}. Para pagar por Pix, acesse: {url}".
- O link vai pronto como prop para a página Cobranças.

### D8. Front mínimo
- **Páginas:** `clientes/Index`, `emprestimos/Index`, `emprestimos/Novo`, `emprestimos/Mostrar` e `cobrancas/Index`, além da `auth/Entrar` que já existe.
- **Novo componente:** só `CampoSelecao`.
- **Formatação:** funções em `resources/js/formatos.ts` (reais, data, telefone).
- **Barra inferior:** 4 itens (3 áreas e Sair), sem o menu "Mais".
- **Tema:** `tokens.css` passa o tema escuro para `@media (prefers-color-scheme: dark)`; saem o script do `<head>` e o `useTema`.
- **Fonte:** a do sistema (`system-ui`).
- **Testes de front:** o Vitest sai; o `vue-tsc` continua no `npm run build`.

### D9. Testes
- **`tests/Unit/CalculoParcelasTest`:** simples, composto, taxa zero, centavos na última, vencimentos 29–31 e ano bissexto.
- **`tests/Feature/WebhookAsaasTest`:** token, evento duplicado, CONFIRMED + RECEIVED, baixa da parcela certa, reserva sem `pixQrCodeId`, parcela já paga por baixa manual e falha respondendo 500.
- Os outros testes saem.

## Risks / Trade-offs

- **[Sem testes de geração e da página `/p`]** → é a Fase 2 já validada; a mudança nela é pequena (tirar juros e conciliação) e é conferida manualmente no fim.
- **[Webhook perdido sem conciliação]** → o Asaas reenvia enquanto respondermos erro; se um pagamento escapar, a baixa manual resolve.
- **[Baixa manual com QR Code ativo]** → se o cliente pagar mesmo assim, o pagamento fica registrado na cobrança e um aviso vai para o log; a parcela não é paga duas vezes.

## Migration Plan

Branch `change/mvp-validacao`. Localmente: `php artisan migrate:fresh` e `php artisan caderneta:usuario`. PR único para a `main`, incluindo os commits da `base-arquitetura`.
