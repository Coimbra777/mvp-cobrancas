# Controle de empréstimos: cobrança Pix (Asaas)

MVP para testar, no **sandbox do Asaas**:

1. a geração da cobrança de cada parcela por **QR Code Pix estático**;
2. a confirmação do pagamento por **webhook**, com baixa automática da parcela.

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

---

## 1. Subir o projeto (Docker)

```sh
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
```

No `.env`, ajuste o banco para o container:

```dosini
APP_URL=http://localhost:8989
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=root
```

Daqui em diante, os comandos `php artisan ...` rodam dentro do container:
`docker-compose exec app php artisan ...`

## 2. Configuração do sandbox

1. Crie uma conta em <https://sandbox.asaas.com>.
2. Gere a chave de API em **Menu do usuário > Integrações > Chaves de API**.
3. Preencha o `.env`:

```dosini
ASAAS_API_URL=https://api-sandbox.asaas.com/v3
ASAAS_API_KEY=            # chave do sandbox
ASAAS_PIX_KEY=            # chave Pix da conta (ver passo 4)
ASAAS_WEBHOOK_TOKEN=      # token do webhook (ver passo 5)
COBRANCA_TAXA_JUROS_MENSAL=2
```

4. Chave Pix: rode `php artisan asaas:chave-pix`. O comando mostra a primeira chave ativa da conta ou, se não houver nenhuma, cria uma chave aleatória (EVP). Copie o valor para `ASAAS_PIX_KEY`.
5. Token do webhook: gere um valor de 32 a 255 caracteres, sem espaços (exigência do Asaas). Exemplo: `openssl rand -hex 32`. O mesmo valor vai no `.env` e no painel (seção 5).

> **Proteção:** com `APP_ENV` diferente de `production`, o sistema só aceita uma URL do Asaas com "sandbox" no host. Qualquer outra URL é recusada antes de a requisição sair.

## 3. Banco e dados de teste

```sh
php artisan migrate --seed
```

O seeder cria 1 cliente, 1 empréstimo e 3 parcelas de R$ 1.050,00: uma **atrasada 3 dias**, uma que **vence hoje** e uma que **vence em 5 dias**. Ele imprime o link `/p/{token}` de cada parcela.

## 4. Túnel local para receber o webhook

O Asaas precisa de uma URL pública HTTPS. Escolha uma das opções:

```sh
# cloudflared (não precisa de conta para túnel temporário)
cloudflared tunnel --url http://localhost:8989

# ou ngrok
ngrok http 8989
```

Anote a URL gerada (ex.: `https://abc-123.trycloudflare.com`). Ela muda a cada execução do túnel temporário, e o cadastro do webhook precisa ser atualizado quando isso acontecer.

## 5. Cadastro do webhook no painel do Asaas

**Menu do usuário > Integrações > Webhooks > Adicionar** (no sandbox):

| Campo | Valor |
|---|---|
| URL | `https://<seu-tunel>/webhooks/asaas` |
| Token de autenticação | o mesmo `ASAAS_WEBHOOK_TOKEN` do `.env` |
| Versão da API | v3 |
| Tipo de envio | sequencial |
| Situação | ativo |
| Eventos (cobranças) | `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED`, `PAYMENT_DELETED`, `PAYMENT_REFUNDED`, `PAYMENT_PARTIALLY_REFUNDED`, `PAYMENT_REFUND_IN_PROGRESS`, `PAYMENT_RECEIVED_IN_CASH_UNDONE`, `PAYMENT_CHARGEBACK_REQUESTED`, `PAYMENT_CHARGEBACK_DISPUTE`, `PAYMENT_AWAITING_CHARGEBACK_REVERSAL` |

Depois de 15 falhas seguidas, o Asaas pode **pausar a fila**. Se o túnel cair, reative a fila no painel depois de subir o túnel de novo.

## 6. Roteiro de teste do zero

1. Suba o projeto, configure o `.env` (seções 1 e 2) e rode `php artisan migrate:fresh --seed`.
2. **Geração pela rotina:** rode `php artisan cobrancas:gerar`. Ela deve criar o QR Code das parcelas que vencem hoje e em 5 dias. A atrasada fica de fora, porque é gerada sob demanda.
   - Rode de novo: deve aparecer "Criadas: 0", ou seja, não duplica.
   - Confira na área Pix do painel do sandbox e na tabela `cobrancas_pix` (status `ativa`, `asaas_qrcode_id` e `payload` preenchidos).
3. **Página de pagamento:** abra os links impressos pelo seeder.
   - Parcela em dia: valor, vencimento, "Em aberto", QR Code e botão "Copiar código Pix".
   - Parcela atrasada: valor com juros (1.050,00 + 2,10 = **R$ 1.052,10** para 3 dias a 2% a.m.), "Em atraso há 3 dias". O QR Code é criado nesse momento e vale até 23:59 de hoje. Recarregar a página reaproveita o mesmo QR Code.
4. **Webhook real (opcional):** no sandbox, pagar um QR Code exige uma segunda conta Asaas. Isso não foi implementado aqui. Se você tiver essa conta, pague o Pix copia e cola por ela e veja o evento chegar.
5. **Webhook simulado** (valida a baixa sem segunda conta):

   ```sh
   # dentro do container, o host do nginx é "nginx"; pelo túnel, use a URL pública
   php artisan webhook:simular 2 --url=http://nginx/webhooks/asaas
   # ou, passando pelo túnel (testa o caminho completo):
   php artisan webhook:simular 2 --url=https://<seu-tunel>/webhooks/asaas
   ```

   - O comando monta um evento `PAYMENT_RECEIVED` no formato do Asaas, com o `pixQrCodeId` do QR Code da parcela. Ele usa como molde o **último evento real** gravado em `eventos_webhook` ou, se ainda não houver nenhum, o formato da documentação.
   - Esperado: HTTP 200. A parcela fica `paga`, com `valor_pago_centavos` e `pago_em` preenchidos. A página `/p/{token}` mostra **"Paga"**.
   - CONFIRMED e RECEIVED do mesmo Pix: `webhook:simular 3 --evento=PAYMENT_CONFIRMED --payment=pay_teste` e depois `webhook:simular 3 --payment=pay_teste`. A baixa acontece uma vez só, e o segundo evento fica com `resultado = ja_baixada:...`.
   - Token inválido: `curl -X POST https://<seu-tunel>/webhooks/asaas -H 'asaas-access-token: errado' -d '{}'` deve responder **401**.

## 7. Qual caminho encontrou a parcela (preencher após o 1º evento real)

A documentação lista `pixQrCodeId` no objeto de cobrança, mas o exemplo de payload do webhook não mostra esse campo. Por isso o sistema tenta, nesta ordem:

1. `payment.pixQrCodeId` do evento → `cobrancas_pix.asaas_qrcode_id`;
2. `payment.externalReference` (`cobranca_pix:{id}`);
3. **alternativa:** `GET /v3/payments/{payment.id}` e repete 1 e 2 com a resposta.

O caminho usado fica gravado em `eventos_webhook.resultado` (ex.: `baixa:pixQrCodeId` ou `baixa:consulta:pixQrCodeId`).

| Data | Evento | `resultado` gravado | Conclusão |
|---|---|---|---|
| _a preencher_ | | | |

## 8. Comandos e agendamento

| Comando | O que faz |
|---|---|
| `cobrancas:gerar` | Cria o QR Code das parcelas em aberto com vencimento **de hoje até hoje + 5**. Pega todas as parcelas da janela (não só as de D-5), então um dia de falha é recuperado na execução seguinte. |
| `webhook:simular {parcela}` | Envia um evento de pagamento simulado (`--evento`, `--payment`, `--url`). |
| `webhook:reprocessar` | Processa de novo eventos gravados cujo processamento falhou. |
| `asaas:chave-pix` | Mostra ou cria a chave Pix usada nos QR Codes. |

O agendamento está em `app/Console/Kernel.php`: `cobrancas:gerar` às 07h e 13h (America/Fortaleza) e `webhook:reprocessar` a cada hora. No servidor, basta **uma** linha de cron:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Para conferir: `php artisan schedule:list`.

## 9. Regras implementadas

- **O Asaas só recebe o valor final:** `addressKey`, `value`, `description` ("Parcela 3 de 10"), `expirationDate`, `allowsMultiplePayments: false` e `externalReference`. Juros e multa ficam só no sistema.
- **Em dia:** o QR Code é criado a partir de D-5 e expira às 23:59:59 do dia do vencimento (horário de Fortaleza).
- **Atrasada:** valor = em aberto + em aberto × (taxa mensal / 30) × dias de atraso, arredondado em centavos (taxa em `COBRANCA_TAXA_JUROS_MENSAL`, padrão 2%). O QR Code é gerado quando a página é aberta e vale até o fim do dia. Se já existir um válido para o mesmo valor, ele é reaproveitado.
- **Troca de QR Code:**
  - Antes de trocar, o sistema consulta no Asaas (`GET /payments?pixQrCodeId=`) se o QR Code antigo já foi pago. Isso cobre um webhook perdido.
  - Em seguida, cria o novo QR Code, remove o antigo (`DELETE /pix/qrCodes/static/{id}`) e o marca como `substituida`.
- **Pagamento de QR Code substituído:** a baixa é feita mesmo assim. A diferença (pago − devido no dia) vai para `cobrancas_pix.diferenca_centavos`, e o sistema gera um log `warning` ("Pix recebido com divergência").
- **Baixa:** `value` é gravado como valor pago e `netValue` em `valor_liquido_centavos` (taxa = value − netValue).
- **Idempotência:**
  - O `id` do evento (`evt_...`) é chave única em `eventos_webhook`; um evento repetido responde 200 sem reprocessar.
  - A baixa também é idempotente pelo `payment.id`, com lock na linha da cobrança.
- **Sem transação em volta da API:** o sistema grava a intenção (`pendente`), chama o Asaas e grava o resultado (`ativa` ou `falhou`).
- **Estornos e exclusões:** só são registrados e geram log `warning`. Reverter uma baixa é decisão manual.
- **Página pública `/p/{token}`:**
  - Token aleatório de 48 caracteres, mostra só o primeiro nome.
  - `noindex` (meta e `X-Robots-Tag`), `Cache-Control: no-store` e limite de 30 acessos por minuto.
- **Baixa manual (dinheiro, parcial):** `Parcela::registrarPagamento($centavos, $data)` soma em `valor_pago_centavos`. Os juros incidem só sobre o valor em aberto.

## 10. Testes automatizados

Usam SQLite em memória e `Http::fake()`, sem chamar o Asaas:

```sh
php artisan test
# sem PHP local:
docker run --rm -u $(id -u):$(id -g) -v "$PWD":/app -w /app php:8.1-cli vendor/bin/phpunit
```

Cobrem:
- campos enviados na criação do QR Code;
- bloqueio da URL de produção;
- recuperação de parcela que ficou para trás, sem duplicar;
- recuperação após falha do Asaas;
- troca de QR Code quando o valor muda;
- webhook com token inválido (401), baixa da parcela certa, evento duplicado e CONFIRMED + RECEIVED baixando uma vez só;
- pagamento de QR Code substituído;
- alternativa sem `pixQrCodeId`;
- estorno;
- página de pagamento;
- cálculo de juros.

## 11. Pontos a confirmar no sandbox

- Formato aceito em `expirationDate` (enviado como `Y-m-d H:i:s`, horário de Brasília/Fortaleza).
- Se o evento real traz `payment.pixQrCodeId` (registrar na seção 7).
- A especificação do Asaas parece trocar as descrições de `id` e `encodedImage` na resposta do QR Code. O sistema usa `id` como identificador e renderiza a imagem a partir do `payload`; conferir na primeira resposta real (log `Asaas: requisição ok` e tabela `cobrancas_pix`).
