# Caderneta

MVP para controlar empréstimos informais de um único usuário:
- cadastro de clientes e empréstimos, com geração automática das parcelas;
- lista de cobranças, com baixa manual e envio pelo WhatsApp;
- cobrança Pix pelo Asaas, com baixa automática.

**Stack:** Laravel 13, Inertia + Vue 3 (TypeScript), MySQL. Planejamento em `openspec/`.

## Requisitos

- **PHP 8.3 ou mais novo** (mínimo do Laravel 13), Composer 2 e MySQL 8 (ou MariaDB 10.6+).
- Node.js 22.12+ **só na máquina de desenvolvimento**, para compilar os assets. Produção não precisa de Node.
- Para rodar local: Docker com Docker Compose (o container já traz PHP 8.3 e MySQL 8.4).

## Setup local

```sh
cp .env.example .env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate   # na 1ª vez o MySQL leva ~20s para subir; se falhar, repita
npm ci && npm run build                       # ou npm run dev para desenvolver
docker compose exec app php artisan caderneta:usuario   # cria (ou redefine) o usuário único
```

Acesse <http://localhost:8989>.

## Variáveis de ambiente (`.env`)

| Variável | Uso |
|---|---|
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Em produção: `production`, `false` e a URL do site (usada nos links `/p/{token}`) |
| `DB_*` | Banco (no Docker local: `caderneta` / `caderneta`) |
| `ASAAS_API_URL` | Sandbox: `https://api-sandbox.asaas.com/v3`. Produção: `https://api.asaas.com/v3`; fora de `APP_ENV=production` só a de sandbox é aceita |
| `ASAAS_API_KEY` | Chave de API do Asaas (segredo) |
| `ASAAS_PIX_KEY` | Chave Pix da conta Asaas que recebe |
| `ASAAS_WEBHOOK_TOKEN` | Token do webhook, de 32 a 255 caracteres (segredo); o mesmo valor vai no painel do Asaas |

Segredos ficam só no `.env`, que nunca vai para o git.

## Webhook do Asaas

No painel do Asaas, em **Integrações > Webhooks**:
- **URL:** `https://SEU_DOMINIO/webhooks/asaas`;
- **token de autenticação:** o mesmo `ASAAS_WEBHOOK_TOKEN`;
- **eventos:** `PAYMENT_CONFIRMED` e `PAYMENT_RECEIVED`.

Para testar localmente, exponha a porta com `cloudflared tunnel --url http://localhost:8989`.

Se o processamento de um evento falhar, o sistema responde 500 e registra a falha no log, e o Asaas reenvia o evento. **Depois de 15 falhas seguidas, o Asaas pausa a fila do webhook.** Para reativar:
1. Corrija a causa no log (`storage/logs/laravel.log`).
2. No painel do Asaas, abra **Integrações > Webhooks**.
3. Reative a fila no webhook pausado. Os eventos pendentes são reenviados.

Enquanto não for confirmado que o evento real traz o `pixQrCodeId`, o sistema também consulta o pagamento na API como reserva. O caminho usado fica em `eventos_webhook.resultado` (`baixa:pixQrCodeId` ou `baixa:consulta`).

## Testes

```sh
docker compose exec app php artisan test   # cálculo das parcelas e webhook (Asaas simulado)
npm run typecheck                          # checagem de tipos do front (também roda no build)
```

## Build dos assets

```sh
npm ci
npm run build   # gera public/build (fora do git)
```

## Deploy (hospedagem compartilhada)

1. Monte o pacote localmente:
   ```sh
   npm ci && npm run build
   composer install --no-dev --optimize-autoloader
   ```
2. Envie o projeto **sem** `.git/`, `node_modules/`, `.env`, `.docker/` e `tests/`, mas **com** `vendor/` e `public/build/`.
3. Aponte o domínio para a pasta `public/`. O resto do projeto, inclusive o `.env`, não pode ficar acessível pela web.
4. No servidor:
   ```sh
   php artisan key:generate        # só na primeira vez
   php artisan migrate --force
   php artisan caderneta:usuario   # só na primeira vez
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
5. Configure o cron (uma linha só, que roda o `cobrancas:gerar` todo dia às 07:00):
   ```cron
   * * * * * /usr/bin/php /home/SEU_USUARIO/caderneta/artisan schedule:run >> /dev/null 2>&1
   ```
