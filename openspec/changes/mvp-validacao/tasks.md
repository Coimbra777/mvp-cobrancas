# Tasks

## 1. Remoções

- [ ] 1.1 Remover os comandos `webhook:simular`, `webhook:reprocessar` e `asaas:chave-pix`, a auto-criação de chave Pix, `config/cobranca.php` e o agendamento do reprocessamento. Pronto quando `php artisan list` não mostra os comandos e `schedule:list` mostra só `cobrancas:gerar`.
- [ ] 1.2 Remover o `Handler` customizado, `Erro.vue`, a página "Em construção", o seeder fictício, os componentes `SeletorTema`, `CampoBusca`, `EstadoCarregando`, `EstadoErro` e `AvatarIniciais`, o `useTema` e o script de tema. Pronto quando `npm run build` passa sem referências a eles.
- [ ] 1.3 Remover o Vitest (testes, helpers, `vitest.config.ts`) e as dependências `vitest`, `@vue/test-utils`, `jsdom`, `axe-core`, `@types/node` e `@fontsource-variable/inter`; usar a fonte do sistema e o tema escuro por `prefers-color-scheme`. Pronto quando `npm ci && npm run build` passa.
- [ ] 1.4 Remover os testes PHP fora de cálculo e webhook. Pronto quando `tests/` só tem o teste de webhook, o teste de cálculo e os helpers.

## 2. Modelo de dados

- [ ] 2.1 Reescrever as migrations e ajustar models e enums (`ParcelaStatus` aberta/paga, novo `MetodoCalculo`), tirando juros e `valor_pago_centavos` da parcela. Pronto quando `php artisan migrate:fresh` roda limpo.

## 3. Fase 2 enxuta

- [ ] 3.1 `CobrancaPixService` sem juros e sem conciliação (valor original; QR Code do dia para atrasada); `WebhookAsaasService` só com `pixQrCodeId` + consulta de reserva; baixa Pix em `CobrancaPix::registrarPagamento` + `Parcela::darBaixa`; webhook responde 500 em falha. Pronto quando o `WebhookAsaasTest` cobre todos os cenários de `confirmacao-pagamento` e passa.

## 4. Clientes e empréstimos

- [ ] 4.1 Criar o `CalculoParcelas` com os testes de unidade da spec `emprestimos`. Pronto quando o `CalculoParcelasTest` passa.
- [ ] 4.2 Criar as rotas, os `FormRequests`, os controllers e as páginas de Clientes (listar/cadastrar) e Empréstimos (listar/novo/mostrar), com o componente `CampoSelecao`. Pronto quando o cadastro funciona no navegador e gera as parcelas certas.

## 5. Cobranças e navegação

- [ ] 5.1 Criar a página Cobranças (filtro atrasadas / a vencer, "Dar baixa", "Enviar no WhatsApp") e a rota de baixa; ajustar a navegação (3 áreas, `/` → `/cobrancas`, barra inferior com Sair). Pronto quando a baixa e o link do WhatsApp funcionam no navegador.

## 6. Documentação e verificação

- [ ] 6.1 Reescrever o README curto (setup, `.env`, testes, build, deploy; PHP 8.3+). Pronto quando os passos de setup funcionam.
- [ ] 6.2 Rodar `php artisan test`, `npm run build` e `openspec validate mvp-validacao --strict`, e fazer a verificação manual no navegador dos fluxos 1 a 5 (inclusive `/p/{token}` e uma baixa pelo webhook com um POST de teste via `curl`). Pronto quando tudo passa.

## Workflow follow-up

- `/opsx:verify`, depois `/opsx:archive` no mesmo PR.
- Abrir o PR para a `main`.
