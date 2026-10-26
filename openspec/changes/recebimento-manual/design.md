# Design

## Context

Ver `proposal.md`. Hoje a Fase 2 do Asaas roda sempre: `cobrancas:gerar`, `CobrancaPixService`, `WebhookAsaasController` e a página `/p/{token}` (Blade, com QR Code desenhado pelo `bacon/bacon-qr-code`). A baixa manual já existe em `Parcela::darBaixa()`. Não há produção.

## Goals / Non-Goals

**Goals:** o modo manual funciona sem nenhuma conta externa; o modo asaas continua exatamente como está; o código novo é pequeno.

**Non-Goals:** os dois modos juntos, leitura automática de comprovantes, notificações ao Dario.

## Decisions

### D1. Modo em `config/services.php`, validado na subida
- **Configuração:** `recebimento.modo` = `env('RECEBIMENTO_MODO', 'manual')`.
- **Validação:** o `AppServiceProvider::boot` lança uma exceção clara se o valor não for `manual` ou `asaas`.
- **Consulta:** um helper estático pequeno, `Recebimento::manual()` / `Recebimento::asaas()` em `app/Support/Recebimento.php`, evita repetir a comparação de string.
- **Alternativa descartada:** a tabela de configurações, já excluída do MVP (o `.env` basta).

### D2. Pix direto num service, sem dependência
- `App\Services\PixDireto::codigo(Parcela $parcela): string` monta o BR Code estático com estes campos:
  - `00` = "01";
  - `26`: `00` = "BR.GOV.BCB.PIX" e `01` = a chave;
  - `52` = "0000", `53` = "986", `54` = o valor com ponto e 2 casas, `58` = "BR";
  - `59` = o nome (até 25 caracteres), `60` = a cidade (até 15 caracteres);
  - `62`: `05` = "***";
  - `63` = o CRC16-CCITT (polinômio 0x1021, valor inicial 0xFFFF) calculado sobre tudo antes, incluindo "6304".
- **Normalização:** `Str::ascii` + `mb_strtoupper` + corte + `trim`.
- **Validação:** o `PixDireto` lança exceção quando falta a chave, o nome ou a cidade; o controller mostra o aviso de falha e registra no log.
- **QR Code:** desenhado do mesmo jeito que hoje (bacon, SVG a partir do código).
- **Por que sem pacote:** o padrão cabe em cerca de 50 linhas e é estável; um pacote seria mais uma dependência para manter.

### D3. Comprovantes: tabela própria e disco privado
- **Tabela `comprovantes`:** `parcela_id`, `arquivo` (caminho no disco), `nome_original`, `mime`, `tamanho_bytes`, `status` (`pendente` | `aprovado` | `recusado`), `analisado_em`, `timestamps`.
- **Enum:** `ComprovanteStatus`.
- **Disco:** o `local` passa a ter a raiz `storage/app/private`, padrão do Laravel 11+, explícito no `config/filesystems.php`. Os arquivos ficam em `comprovantes/AAAA/MM/<uuid>.<ext>`; o nome original é só registrado, nunca usado como caminho.
- **Envio:** `POST /p/{token}/comprovante`, dentro do grupo web, com CSRF, porque a página Blade já tem sessão.
  - Validação: `file|mimes:jpg,jpeg,png,webp,pdf|max:5120`.
  - Limites: `throttle:5,60` por IP; parcela aberta; no máximo 3 pendentes.
- **Arquivo para o Dario:** `GET /comprovantes/{comprovante}/arquivo` dentro do grupo `auth`. Responde `Storage::response(...)` com `Content-Disposition: inline` e `X-Content-Type-Options: nosniff`.
- **Revisão:** `POST /comprovantes/{comprovante}/baixa` chama `$parcela->darBaixa()`, aprova este e recusa os outros pendentes, numa transação. `POST /comprovantes/{comprovante}/recusa` recusa só este.
- **Rotas de comprovantes no painel:** atrás de um middleware simples, `modo:manual`, que responde 404 no modo asaas.

### D4. Ajustes no modo asaas
- `GerarCobrancas` sai cedo no modo manual ("Modo manual: nada a gerar.").
- `WebhookAsaasController` responde 404 no modo manual antes de qualquer gravação.
- `PagamentoController` escolhe a fonte do Pix pelo modo: `CobrancaPixService` (asaas) ou `PixDireto` (manual). A view recebe o código e o QR Code iguais nos dois casos, mais o estado do último comprovante, só no modo manual.

### D5. Front
- **Nova página:** `comprovantes/Index.vue`, uma `TabelaResponsiva` com colunas Cliente, Parcela, Valor, Vencimento, Enviado em e Arquivo (link "Abrir comprovante" em nova aba), e as ações "Dar baixa" e "Recusar", ambas com confirmação.
- **Props compartilhadas:**
  - `app.modo` controla a navegação, e `AREAS` ganha "Comprovantes" condicionalmente;
  - `comprovantes_pendentes` (contagem) só no modo manual, feita no `HandleInertiaRequests` com um `count()` barato.
- **Cobranças:** cada parcela traz `comprovante_pendente: bool`; o selo é um `SeloSituacao` neutro com o rótulo "Comprovante enviado".
- **Página pública (Blade):** formulário `multipart` com `<input type="file" accept="image/jpeg,image/png,image/webp,application/pdf">`, o botão "Enviar comprovante" e mensagens de sucesso ou erro na sessão (flash).

### D6. Testes
- `tests/Unit/PixDiretoTest`: monta o código para um caso fixo e confere a estrutura e o CRC16 contra o valor calculado à parte. Também verifica a normalização de acentos e os cortes.
- `tests/Feature/ComprovanteTest`:
  - envio válido e inválido, e o limite de 3 pendentes;
  - parcela paga recusa o envio;
  - o arquivo exige sessão;
  - "Dar baixa" paga a parcela e recusa os outros pendentes;
  - "Recusar";
  - 404 das rotas no modo asaas;
  - webhook e rotina no modo manual.

## Risks / Trade-offs

- **[Comprovante falso ou de outro valor]** → a conferência é humana; o Dario abre o arquivo e confere no app do banco antes de dar baixa. A tela mostra o valor esperado ao lado.
- **[Abuso da página pública com uploads]** → limite de tamanho e tipo, 5 envios por hora por IP, 3 pendentes por parcela e armazenamento fora da área pública.
- **[Espaço em disco na hospedagem]** → arquivos de até 5 MB, cerca de 50 por mês; menos de 250 MB por mês no pior caso.
- **[Código Pix recusado por algum banco]** → teste manual com 2 apps de banco antes de mostrar ao Dario (tarefa de verificação).

## Migration Plan

- **Migration nova** `create_comprovantes_table`. É aditiva: o banco local não precisa de `migrate:fresh`.
- **`.env.example`:** `RECEBIMENTO_MODO=manual`, `PIX_CHAVE`, `PIX_NOME_RECEBEDOR` e `PIX_CIDADE`.
- **Hospedagem:** `storage/app/private` precisa ter permissão de escrita (o README já cobre `storage/`).
