# Proposal

## Why

O objetivo agora é validar a Caderneta com o cliente o quanto antes, com o MVP mais simples possível: cadastrar clientes e empréstimos, ver o que está em aberto, dar baixa, cobrar pelo WhatsApp e receber por Pix. Tudo que não serve a isso sai (lembretes, juros, parcial, renegociação, configurações, painel, testes de front), para que o produto seja pequeno, fácil de mudar e rápido de pôr na frente do Dario.

## What Changes

- **Clientes:** listar e cadastrar (nome e telefone).
- **Empréstimos:**
  - cadastrar com cliente, valor, taxa mensal, método (simples \| composto), nº de parcelas e 1º vencimento;
  - ao salvar, gerar as parcelas (mesmo dia nos meses seguintes, sem estouro em 29–31, centavos inteiros, diferença de centavos na última);
  - listar empréstimos e mostrar as parcelas de cada um.
- **Cobranças:**
  - página com as parcelas em aberto (filtro: atrasadas / a vencer);
  - "Dar baixa" marca a parcela como paga hoje, sem parcial;
  - "Enviar no WhatsApp" abre um link `wa.me` com mensagem pronta e o link da página `/p/{token}`.
- **Fase 2 mantida:**
  - `cobrancas:gerar` diário;
  - página `/p/{token}`;
  - webhook com baixa automática.

  A parcela atrasada recebe um QR Code do dia com o **valor original** (sem juros). O webhook passa a responder 500 em falha, para o Asaas reenviar.
- **Navegação:** áreas Clientes, Empréstimos e Cobranças; `/` leva a Cobranças; a barra inferior do celular fica com as 3 áreas e "Sair".
- **Removido:**
  - Na Fase 2: comandos `webhook:simular`, `webhook:reprocessar` e `asaas:chave-pix`, auto-criação de chave Pix, juros de atraso (`config/cobranca.php`), conciliação antes de trocar o QR Code, lista de eventos de alerta e registro de diferença.
  - No back-end: `Handler`/`Erro.vue` customizados, seeder fictício, áreas Painel/Parcelas/Lembretes/Configurações e a página "Em construção".
  - No front-end: seletor de tema (o tema escuro fica automático), `CampoBusca`, `EstadoCarregando`, `EstadoErro`, `AvatarIniciais`, menu "Mais", fonte Inter e Vitest com as suas dependências.
  - Nos testes: os testes PHP fora de cálculo e webhook.
- **Migrations reescritas** para o modelo enxuto (não há produção).
- **README curto:** setup, `.env`, testes, build e deploy.

## Capabilities

### New Capabilities
- `clientes`: listar e cadastrar clientes.
- `emprestimos`: cadastro de empréstimo com geração das parcelas (cálculo simples/composto e vencimentos).
- `cobrancas`: parcelas em aberto, baixa manual e envio da cobrança pelo WhatsApp.
- `geracao-cobranca`: QR Code Pix estático no Asaas (rotina diária e QR Code do dia para parcela atrasada).
- `pagina-pagamento`: página pública `/p/{token}`.
- `confirmacao-pagamento`: webhook do Asaas com baixa automática idempotente.

### Modified Capabilities
- `autenticacao`: o destino depois de entrar passa de `/painel` para `/cobrancas`.
- `navegacao-painel`:
  - áreas reduzidas a Clientes, Empréstimos e Cobranças;
  - barra inferior sem o menu "Mais";
  - páginas de erro customizadas removidas.
- `sistema-visual`: tema só automático; os estados de carregando e erro saem, e fica o estado vazio.

## Impact

- **Banco:** migrations reescritas; o banco local precisa de `migrate:fresh`.
- **Código:**
  - novos models/enums: `MetodoCalculo`;
  - novos services: `CalculoParcelas`;
  - novos controllers: Clientes, Empréstimos, Cobranças;
  - novas páginas Vue: 5, mais o componente `CampoSelecao`;
  - removidos: 3 comandos, `BaixaPixService` (incorporado aos models), 5 componentes, `useTema`, `Erro.vue` e a suíte Vitest.
- **Dependências:** saem `@fontsource-variable/inter`, `vitest`, `@vue/test-utils`, `jsdom`, `axe-core` e `@types/node`; nenhuma entra.
- **Asaas:** nenhuma mudança no que é enviado.

## Fora do escopo

- Lembretes (ativação, consentimento, fila, registro de envios).
- Juros de atraso, pagamento parcial e renegociação.
- Pix direto/Plano B, tabela de configurações (usa-se o `.env`), backup automático e painel com indicadores.
- Edição/exclusão de clientes e empréstimos, prévia das parcelas antes de salvar e seletor de tema.
