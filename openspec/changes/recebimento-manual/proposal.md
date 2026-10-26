# Proposal

## Why

Hoje o sistema só recebe por Pix com o Asaas: sem as chaves, a página de pagamento mostra erro e nada funciona. O Dario quer poder operar também sem o Asaas: o cliente paga um Pix direto na chave do banco dele, envia o comprovante pela própria página de pagamento, e o Dario confere numa tela separada e dá a baixa. Com os dois modos, o MVP pode ser validado já, mesmo antes de a conta Asaas existir.

## What Changes

- **Modo de recebimento no `.env`** (`RECEBIMENTO_MODO=manual|asaas`, padrão `manual`), um de cada vez:
  - **`asaas`:** como hoje (QR Code estático do Asaas, rotina diária, webhook com baixa automática).
  - **`manual`:** nada do Asaas roda.
    - A rotina `cobrancas:gerar` não cria nada, e o webhook responde 404.
    - A página `/p/{token}` mostra o Pix copia e cola e o QR Code gerados pelo próprio sistema, com a chave Pix do Dario e o valor da parcela, no padrão do Banco Central, sem dependência nova.
- **Comprovantes** (só no modo manual):
  - **Envio:** o cliente anexa a foto ou o PDF do comprovante na página `/p/{token}` (JPG, PNG, WEBP ou PDF, até 5 MB).
  - **Armazenamento:** o arquivo fica guardado fora da área pública.
  - **Revisão:** nova tela "Comprovantes", onde o Dario vê os comprovantes pendentes, abre o arquivo e escolhe "Dar baixa" (a parcela fica paga) ou "Recusar".
  - **Retorno ao cliente:** a página mostra "Comprovante enviado, aguardando confirmação" ou "Comprovante recusado, envie outro".
  - **Na lista de Cobranças:** a parcela com comprovante pendente ganha o selo "Comprovante enviado".
- **Navegação:** no modo manual entra a área "Comprovantes", com a contagem de pendentes.
- **README:** configuração dos dois modos.

## Capabilities

### New Capabilities
- `modo-recebimento`: escolha entre os modos manual e asaas, e o que cada um liga ou desliga.
- `pix-direto`: geração do Pix copia e cola e do QR Code com a chave do Dario, no padrão BR Code do Banco Central.
- `comprovantes`: envio do comprovante pelo cliente e revisão pelo Dario (dar baixa ou recusar).

### Modified Capabilities
- `pagina-pagamento`: o conteúdo depende do modo; no manual, o Pix vem da chave do Dario, não há janela de 5 dias e a página recebe o comprovante.
- `geracao-cobranca`: a rotina diária só cria QR Codes no modo asaas.
- `confirmacao-pagamento`: o webhook só funciona no modo asaas.
- `cobrancas`: selo "Comprovante enviado" nas parcelas com comprovante pendente.
- `navegacao-painel`: área "Comprovantes" no modo manual.

## Impact

- **Novos:**
  - tabela e model `comprovantes`;
  - service `PixDireto`, que monta o código BR Code com CRC16;
  - controllers `ComprovanteController` (cliente e Dario);
  - página `comprovantes/Index`;
  - testes do código Pix e do fluxo de comprovante.
- **Alterados:** `PagamentoController` e a view pública, `GerarCobrancas`, `WebhookAsaasController`, a página de Cobranças, a navegação, o `.env.example` (`RECEBIMENTO_MODO`, `PIX_CHAVE`, `PIX_NOME_RECEBEDOR`, `PIX_CIDADE`) e o README.
- **Dependências:** nenhuma nova. O QR Code continua desenhado pelo `bacon/bacon-qr-code`, que já está no projeto.
- **Armazenamento:** comprovantes em `storage/app/private/comprovantes`, que precisa ter permissão de escrita na hospedagem.

## Fora do escopo

- Os dois modos ao mesmo tempo.
- Leitura automática do comprovante (OCR) ou conferência no extrato do banco.
- Notificar o Dario quando chega um comprovante; ele vê a contagem na tela.
- Pagamento parcial e juros de atraso; o valor é sempre o da parcela.
- Excluir comprovantes. Eles ficam guardados como registro.
