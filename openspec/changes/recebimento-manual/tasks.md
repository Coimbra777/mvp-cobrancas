# Tasks

## 1. Modo de recebimento

- [ ] 1.1 Adicionar `recebimento.modo` e `pix.*` em `config/services.php`, a validação na subida (`AppServiceProvider`), o helper `Recebimento` e o middleware `modo:manual`; atualizar `.env.example` e `phpunit.xml` (testes no modo asaas por padrão, para manter os testes do webhook). Pronto quando o sistema sobe com `manual` e `asaas` e recusa outro valor com a mensagem da spec.
- [ ] 1.2 `cobrancas:gerar` sai cedo no modo manual e o webhook responde 404 no modo manual. Pronto quando os testes desses dois cenários passam e os testes atuais do webhook continuam verdes.

## 2. Pix direto

- [ ] 2.1 Criar `PixDireto` (BR Code + CRC16 + normalização) e `tests/Unit/PixDiretoTest`. Pronto quando o teste confere a estrutura e o CRC do caso fixo, além dos acentos e cortes da spec.
- [ ] 2.2 `PagamentoController` e a view pública usam o `PixDireto` no modo manual (sem janela de 5 dias). Pronto quando a página de uma parcela que vence em 2 meses mostra o Pix e o QR Code no modo manual, e o código copiado é aceito por 2 apps de banco (verificação manual).

## 3. Comprovantes

- [ ] 3.1 Migration, model e enum `Comprovante`; disco privado; envio pelo cliente (`POST /p/{token}/comprovante`) com validação e limites; estado do comprovante na página pública. Pronto quando os testes de envio válido e inválido, limite de 3 e parcela paga passam.
- [ ] 3.2 Tela "Comprovantes" (`GET /comprovantes`, arquivo protegido, "Dar baixa", "Recusar"), selo em Cobranças e navegação com a contagem. Pronto quando os testes de baixa, recusa, arquivo protegido e 404 no modo asaas passam e o fluxo funciona no navegador.

## 4. Documentação e verificação

- [ ] 4.1 README com os dois modos e as variáveis novas; atualizar o guia do MVP. Pronto quando o README explica como trocar de modo.
- [ ] 4.2 Rodar `php artisan test`, `npm run build` e `openspec validate recebimento-manual --strict`, e verificar no navegador os dois modos de ponta a ponta. Pronto quando tudo passa.

## Workflow follow-up

- `/opsx:verify`, depois `/opsx:archive` no mesmo PR.
- Abrir o PR para a `main`.
