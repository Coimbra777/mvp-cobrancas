# Caderneta

Sistema web para controlar empréstimos informais: clientes, empréstimos, parcelas, cobrança por Pix (Asaas) e lembretes. Feito para **um único usuário**.

- **Stack:** Laravel 13 (PHP 8.3), Inertia.js 3 + Vue 3 + TypeScript, Vite e MySQL.
- **Produção:** hospedagem compartilhada (Hostinger), **sem Node no servidor**. Os assets são compilados antes do deploy e não ficam no git.
- **Planejamento:** cada mudança é especificada com [OpenSpec](https://github.com/Fission-AI/OpenSpec) em `openspec/` (contexto em `openspec/config.yaml`).

---

## Sumário

1. [Requisitos](#1-requisitos)
2. [Setup local](#2-setup-local)
3. [Variáveis de ambiente](#3-variáveis-de-ambiente)
4. [Front-end: desenvolvimento e build](#4-front-end-desenvolvimento-e-build)
5. [Testes](#5-testes)
6. [Publicação na Hostinger](#6-publicação-na-hostinger)
7. [Agendamento (cron)](#7-agendamento-cron)
8. [Cobrança Pix pelo Asaas (sandbox)](#8-cobrança-pix-pelo-asaas-sandbox)
9. [Estrutura do projeto](#9-estrutura-do-projeto)

---

## 1. Requisitos

| Para | Precisa de |
|---|---|
| Rodar o back-end local | Docker com Docker Compose (o container traz PHP 8.3 e MySQL 8.4) |
| Compilar o front-end | Node.js **22.12+** e npm, só na máquina de desenvolvimento ou no CI |
| Produção | PHP 8.3+, MySQL (ou MariaDB) e cron; Node **não** é necessário |

Sem Docker, também dá para rodar com PHP 8.3, Composer 2 e MySQL 8 instalados na máquina; ajuste `DB_HOST` no `.env`.

## 2. Setup local

```sh
cp .env.example .env                         # valores locais; o .env nunca vai para o git
docker compose up -d --build                 # app (PHP 8.3), nginx em :8989 e MySQL 8.4 em :3388
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

npm ci
npm run build                                # gera public/build (ou use npm run dev, seção 4)

docker compose exec app php artisan caderneta:usuario   # cria o usuário (pede nome, e-mail e senha)
```

Acesse <http://localhost:8989> e entre com o e-mail e a senha que você definiu.

- **Comandos artisan** rodam dentro do container: `docker compose exec app php artisan ...`.
- **Usuário único:** o `caderneta:usuario` também serve para trocar nome, e-mail ou senha depois. Ele sempre atualiza o mesmo usuário; não existe cadastro público.
- **Dados de teste:** o seeder cria 1 cliente, 1 empréstimo e 3 parcelas, e imprime os links das páginas públicas de pagamento.
- **Banco local:** o `docker-compose.yml` cria o banco e o usuário `caderneta` a partir de `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`. Os dados ficam em `.docker/mysql/dbdata` (fora do git). Se trocar a versão do MySQL, apague essa pasta e rode `migrate --seed` de novo.

## 3. Variáveis de ambiente

Todas ficam no `.env`. O `.env.example` traz só placeholders e valores locais.

| Variável | Local | Produção | Observação |
|---|---|---|---|
| `APP_NAME` | `Caderneta` | `Caderneta` | |
| `APP_ENV` | `local` | `production` | Fora de `production`, o sistema recusa a URL de produção do Asaas |
| `APP_KEY` | gerada pelo `key:generate` | gerada uma vez no servidor | Nunca reutilize a chave local |
| `APP_DEBUG` | `true` | **`false`** | Com `false`, as páginas de erro não mostram detalhes técnicos |
| `APP_URL` | `http://localhost:8989` | `https://seu-dominio` | Usado nos links `/p/{token}` |
| `APP_TIMEZONE` | `America/Fortaleza` | idem | |
| `DB_HOST`, `DB_PORT` | `db`, `3306` | do hPanel | |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `caderneta` (Docker) | do hPanel | |
| `SESSION_DRIVER` | `file` | `file` | |
| `SESSION_SECURE_COOKIE` | — | `true` | Cookie de sessão só por HTTPS |
| `LOG_LEVEL` | `debug` | `warning` | |
| `ASAAS_API_URL` | `https://api-sandbox.asaas.com/v3` | `https://api.asaas.com/v3` | |
| `ASAAS_API_KEY` | chave do sandbox | chave de produção | Segredo |
| `ASAAS_PIX_KEY` | chave Pix da conta sandbox | chave Pix da conta | Veja `php artisan asaas:chave-pix` |
| `ASAAS_WEBHOOK_TOKEN` | 32 a 255 caracteres | outro valor, só de produção | Segredo; o mesmo valor vai no painel do Asaas |
| `COBRANCA_TAXA_JUROS_MENSAL` | `2` | `2` | % ao mês, pró-rata por dia de atraso |

## 4. Front-end: desenvolvimento e build

O painel é feito de páginas Vue renderizadas pelo Inertia. Elas ficam em `resources/js`:

| Pasta | Conteúdo |
|---|---|
| `pages/` | Uma página por rota (ex.: `auth/Entrar.vue`, `EmConstrucao.vue`, `Erro.vue`) |
| `layouts/` | `PainelLayout` (sidebar/barra inferior) e `VisitanteLayout` (login e erros) |
| `components/` | Componentes base (`Botao`, `Card`, `SeloSituacao`, `CampoTexto`, `TabelaResponsiva`, estados...) |
| `composables/` | `useTema` (claro/escuro/automático) |
| `navegacao.ts` | Áreas do painel (fonte única da navegação) |

Estilos: `resources/css/tokens.css` (cores, tipografia, espaçamento, raios, para os temas claro e escuro) e `resources/css/base.css`. Os componentes usam só os tokens.

```sh
npm run dev         # Vite com recarga automática; o Laravel no Docker carrega os assets de localhost:5173
npm run build       # checa os tipos (vue-tsc) e gera public/build para produção
npm run typecheck   # só a checagem de tipos
```

- `public/build/` e `public/hot` estão no `.gitignore`; **nunca commite arquivos compilados**.
- O TypeScript está fixado em `~6.0`, porque o `vue-tsc` ainda não roda com o TypeScript 7.

## 5. Testes

```sh
docker compose exec app php artisan test   # PHPUnit: SQLite em memória, Asaas simulado com Http::fake()
npm test                                   # Vitest + Vue Test Utils (jsdom) + axe-core
npm run typecheck
```

- **PHP:** autenticação, comando do usuário, rotas públicas (com o CSRF ligado de verdade), páginas de erro, props compartilhadas, cobrança Pix, webhook e página de pagamento.
- **Front:** componentes, casca do painel, tela de entrada, tema, contraste AA dos tokens (lê o `tokens.css`) e checagem automática de acessibilidade com o axe-core.
- **Sem PHP local:** os testes de back-end também rodam sem o Docker do projeto:

  ```sh
  docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.3-cli vendor/bin/phpunit
  ```

## 6. Publicação na Hostinger

A hospedagem não tem Node, e o git não guarda `vendor/` nem `public/build/`. Por isso o pacote de deploy é montado na máquina local e enviado pronto.

> **A confirmar no hPanel do plano:**
> - versão do PHP (escolher 8.3 ou mais nova);
> - motor e versão do banco;
> - se há acesso SSH;
> - caminho do binário PHP para o cron;
> - se o domínio pode apontar direto para a pasta `public/`.
>
> A change `backup-e-deploy` fecha esses pontos e automatiza o processo.

### 6.1 Montar o pacote (na sua máquina)

```sh
git checkout main && git pull
npm ci && npm run build
docker compose exec app composer install --no-dev --optimize-autoloader
```

Envie o projeto **sem**: `.git/`, `node_modules/`, `.env`, `.docker/`, `tests/`, `storage/logs/*` e `storage/framework/*/*`. Mantenha os `.gitignore` internos de `storage/`.

Depois do deploy, volte as dependências de desenvolvimento na sua máquina com `docker compose exec app composer install`.

### 6.2 Onde colocar os arquivos

O navegador só pode enxergar a pasta `public/`. O resto do projeto (incluindo o `.env`) precisa ficar fora do alcance da web.

- **Opção A (preferida, exige SSH):** suba o projeto para `~/caderneta` e troque o `public_html` do domínio por um link simbólico:

  ```sh
  mv ~/domains/SEU_DOMINIO/public_html ~/domains/SEU_DOMINIO/public_html.antigo
  ln -s ~/caderneta/public ~/domains/SEU_DOMINIO/public_html
  ```

- **Opção B (sem SSH):** se o hPanel permitir mudar a pasta raiz do domínio, aponte-a para `caderneta/public`.
- **Evite** colocar o projeto inteiro dentro de `public_html` com um `.htaccess` de redirecionamento: um erro de configuração expõe o `.env`.

### 6.3 Primeira vez no servidor

1. Crie o banco e o usuário no hPanel.
2. Crie o `.env` de produção a partir do `.env.example` (valores da seção 3: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, credenciais do banco e do Asaas de produção).
3. Rode no servidor:

   ```sh
   php artisan key:generate
   php artisan migrate --force
   php artisan caderneta:usuario
   ```

### 6.4 A cada deploy

```sh
php artisan down
# envie os arquivos (6.1)
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

## 7. Agendamento (cron)

As rotinas ficam no scheduler do Laravel (`app/Console/Kernel.php`):

| Comando | Quando |
|---|---|
| `cobrancas:gerar` | 07h e 13h (America/Fortaleza) |
| `webhook:reprocessar` | de hora em hora |

No servidor basta **uma** linha de cron (hPanel → Avançado → Cron Jobs), ajustando o caminho do PHP e do projeto:

```cron
* * * * * /usr/bin/php /home/SEU_USUARIO/caderneta/artisan schedule:run >> /dev/null 2>&1
```

Para conferir: `php artisan schedule:list`.

## 8. Cobrança Pix pelo Asaas (sandbox)

Trabalho da Fase 2: QR Code Pix **estático** por parcela e baixa automática pelo webhook.

```
cobrancas:gerar (07h e 13h)           cliente abre /p/{token}
  parcelas que vencem em até 5 dias     parcela atrasada: QR Code do dia, sob demanda
            │                                   │
            └──────► POST /v3/pix/qrCodes/static ◄┘   (valor final, sem multa/juros)
                                │
                      cliente paga o Pix
                                │
      Asaas cria a cobrança e envia PAYMENT_CONFIRMED / PAYMENT_RECEIVED
                                │
          POST /webhooks/asaas ─► grava o evento ─► acha a parcela pelo pixQrCodeId ─► baixa
```

### 8.1 Configurar o sandbox

1. Crie uma conta em <https://sandbox.asaas.com> e gere a chave de API em **Menu do usuário > Integrações > Chaves de API**.
2. Preencha `ASAAS_API_URL`, `ASAAS_API_KEY` e `ASAAS_WEBHOOK_TOKEN` no `.env`. Para o token, gere 32 a 255 caracteres sem espaços, por exemplo com `openssl rand -hex 32`.
3. Rode `php artisan asaas:chave-pix`. Ele mostra a primeira chave Pix ativa da conta ou cria uma aleatória (EVP). Copie o valor para `ASAAS_PIX_KEY`.

> Com `APP_ENV` diferente de `production`, o sistema só aceita uma URL do Asaas com "sandbox" no host.

### 8.2 Túnel local e cadastro do webhook

O Asaas precisa de uma URL pública HTTPS:

```sh
cloudflared tunnel --url http://localhost:8989   # ou: ngrok http 8989
```

No painel do sandbox, em **Menu do usuário > Integrações > Webhooks > Adicionar**:

| Campo | Valor |
|---|---|
| URL | `https://<seu-tunel>/webhooks/asaas` |
| Token de autenticação | o mesmo `ASAAS_WEBHOOK_TOKEN` |
| Versão da API / envio | v3, sequencial |
| Eventos | `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED`, `PAYMENT_DELETED`, `PAYMENT_REFUNDED`, `PAYMENT_PARTIALLY_REFUNDED`, `PAYMENT_REFUND_IN_PROGRESS`, `PAYMENT_RECEIVED_IN_CASH_UNDONE`, `PAYMENT_CHARGEBACK_REQUESTED`, `PAYMENT_CHARGEBACK_DISPUTE`, `PAYMENT_AWAITING_CHARGEBACK_REVERSAL` |

Depois de 15 falhas seguidas, o Asaas pode pausar a fila. Se o túnel cair, reative a fila no painel.

### 8.3 Roteiro de teste

1. `php artisan migrate:fresh --seed` e depois `php artisan cobrancas:gerar`.
   - A rotina cria o QR Code das parcelas que vencem hoje e em 5 dias.
   - Rodar de novo deve mostrar "Criadas: 0".
2. Abra os links `/p/{token}` impressos pelo seeder.
   - A parcela atrasada mostra o valor com juros (1.050,00 + 2,10 = **R$ 1.052,10** para 3 dias a 2% a.m.).
   - O QR Code dela é criado na hora e vale até 23:59 de hoje.
3. Pagar um QR Code no sandbox exige uma segunda conta Asaas, que não faz parte deste projeto. Para validar a baixa, simule o webhook:

   ```sh
   php artisan webhook:simular 2 --url=http://nginx/webhooks/asaas   # de dentro do container
   php artisan webhook:simular 2 --url=https://<seu-tunel>/webhooks/asaas
   ```

   - Esperado: HTTP 200, parcela `paga` e a página `/p/{token}` mostrando **"Paga"**.
   - Para simular CONFIRMED e RECEIVED do mesmo Pix, use `--evento=PAYMENT_CONFIRMED --payment=pay_teste` e depois `--payment=pay_teste`; a baixa acontece uma vez só.

### 8.4 Como a parcela é encontrada (preencher após o 1º evento real)

O sistema tenta, nesta ordem:

1. `payment.pixQrCodeId` do evento;
2. `payment.externalReference` (`cobranca_pix:{id}`);
3. consulta `GET /v3/payments/{id}`, repetindo 1 e 2 com a resposta.

O caminho que funcionou fica em `eventos_webhook.resultado` (ex.: `baixa:pixQrCodeId`).

| Data | Evento | `resultado` | Conclusão |
|---|---|---|---|
| _a preencher_ | | | |

### 8.5 Regras implementadas

- **O Asaas recebe só o valor final:** `addressKey`, `value`, `description`, `expirationDate`, `allowsMultiplePayments: false` e `externalReference`. Multa e juros ficam só no sistema.
- **Expiração:** parcela em dia expira às 23:59:59 do vencimento; parcela atrasada recebe um QR Code do dia, sob demanda, com juros = em aberto × (taxa/30) × dias de atraso.
- **Troca de QR Code:**
  - antes de trocar, o sistema confere no Asaas se o QR Code antigo já foi pago;
  - remove o antigo (`DELETE`) e o marca como `substituida`;
  - se um QR Code substituído for pago, a baixa acontece mesmo assim, com a diferença registrada e um alerta no log.
- **Idempotência:** o `id` do evento é chave única; a baixa é idempotente pelo `payment.id`; o valor pago é o `value` e o `netValue` fica guardado à parte.

## 9. Estrutura do projeto

```
app/
  Console/Commands/        cobrancas:gerar, webhook:simular, webhook:reprocessar, asaas:chave-pix, caderneta:usuario
  Http/Controllers/        LoginController, PagamentoController (/p/{token}), WebhookAsaasController
  Http/Middleware/         HandleInertiaRequests (props compartilhadas), VerifyCsrfToken (exceção do webhook)
  Services/                AsaasClient, CobrancaPixService, BaixaPixService, WebhookAsaasService
resources/js/              painel em Vue 3 + TypeScript (seção 4)
resources/views/           app.blade.php (raiz do Inertia) e pagamento/show.blade.php (página pública)
openspec/                  contexto do projeto, specs e changes
tests/Feature, tests/Unit  PHPUnit
tests/js                   Vitest
```

**Fluxo de trabalho:**
- uma branch por change do OpenSpec (`change/<nome>`), com commits pequenos em português, no imperativo;
- Pull Request para a `main` com revisão antes do merge;
- sem push forçado na `main` e sem arquivos compilados no git.
