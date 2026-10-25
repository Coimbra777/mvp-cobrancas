# Spec Delta

## Purpose

Delimita o que a Caderneta expõe sem login (o webhook do Asaas e a página pública de pagamento) e garante que essas rotas sigam funcionando enquanto o resto do sistema fica protegido.

## ADDED Requirements

### Requirement: Webhook do Asaas acessível sem sessão e sem CSRF
O endpoint `POST /webhooks/asaas` SHALL aceitar requisições sem sessão e sem token CSRF, autenticadas apenas pelo cabeçalho `asaas-access-token`, e SHALL responder em JSON.

#### Scenario: Chamada do Asaas sem sessão
- **WHEN** o Asaas envia um evento com o `asaas-access-token` correto e sem cookies
- **THEN** o sistema responde 200 em JSON, sem redirecionar para `/entrar` e sem erro 419

#### Scenario: Token inválido
- **WHEN** a requisição chega sem o cabeçalho ou com valor errado
- **THEN** o sistema responde 401 em JSON

### Requirement: Página pública de pagamento sem login
A página `GET /p/{token}` SHALL abrir sem sessão, não SHALL exigir nem oferecer login e SHALL ter `noindex`. Ela MUST NOT depender dos assets do painel nem do Inertia.

#### Scenario: Cliente abre o link
- **WHEN** um visitante sem sessão abre `/p/{token}` com um token válido
- **THEN** a página responde 200 com o cabeçalho `X-Robots-Tag: noindex, nofollow`, sem redirecionar para `/entrar`

#### Scenario: Assets do painel não compilados
- **WHEN** os assets do painel não estão compilados no servidor
- **THEN** a página `/p/{token}` continua abrindo normalmente

### Requirement: Todo o resto exige sessão
Toda rota fora de `/entrar`, `/webhooks/asaas`, `/p/{token}` e dos arquivos estáticos SHALL exigir sessão autenticada.

#### Scenario: Rota nova sem proteção explícita
- **WHEN** uma rota do painel é aberta sem sessão
- **THEN** o sistema redireciona para `/entrar`, pois as rotas do painel ficam dentro de um grupo protegido por padrão
