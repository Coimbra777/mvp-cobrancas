# Design

## Context

Motivação e escopo: ver `proposal.md`. Requisitos: specs `autenticacao`, `navegacao-painel`, `sistema-visual` e `rotas-publicas`.

Estado atual do repositório (commit inicial da `main`):

- **Framework e ambiente:**
  - Laravel 10.50 com a estrutura clássica (`app/Http/Kernel.php`, `app/Console/Kernel.php`, `RouteServiceProvider`);
  - `composer.json` com `php: ^8.1`; `laravel/sanctum` instalado mas sem uso real;
  - `routes/api.php` com a rota de exemplo `/api/user`;
  - Docker com `php:8.1-fpm`, nginx na porta 8989, `mysql:5.7.22` e redis sem uso;
  - `package.json` do boilerplate (Vite + laravel-vite-plugin, sem framework de front);
  - fuso padrão `America/Fortaleza` e tabela `users` padrão do Laravel (`name`, `email`, `password`).
- **Fase 2, a preservar:**
  - `POST /webhooks/asaas` em `routes/web.php`, com exceção no `VerifyCsrfToken`;
  - `GET /p/{token}` em Blade autocontido, com `throttle:30,1`;
  - comandos `cobrancas:gerar`, `webhook:simular`, `webhook:reprocessar` e `asaas:chave-pix`, agendados no `Console/Kernel`;
  - 24 testes PHPUnit (SQLite em memória, `Http::fake()`).

Restrições: produção numa hospedagem compartilhada sem Node; um único usuário; tudo em pt-BR.

## Goals / Non-Goals

**Goals:**
- Atualizar o framework sem mudar o comportamento da Fase 2 e provar isso pelos testes existentes.
- Deixar pronto o padrão que todas as próximas changes vão seguir: página Inertia, layout, componentes, tokens, testes de front e de back.
- Manter a quantidade de dependências pequena e cada uma justificada.

**Non-Goals:**
- Migrar para a estrutura enxuta do Laravel 11+ (`bootstrap/app.php` sem Kernels); a estrutura atual continua suportada.
- SSR do Inertia (exigiria Node em produção).
- Biblioteca de componentes de terceiros ou framework CSS utilitário.

## Decisions

### D1. Atualização no lugar, 10 → 11 → 12 → 13 (requer aprovação)

- **Como:**
  - subir as restrições do `composer.json` uma versão por vez, seguindo o guia oficial de cada salto, com um commit por versão e a suíte verde em cada um;
  - fixar `config.platform.php = 8.3.0` no `composer.json`, para o lock resolver pacotes compatíveis com a hospedagem;
  - remover `laravel/sanctum` e `routes/api.php`; nenhuma rota usa token e a spec `autenticacao` proíbe API pública.
- **Alternativa considerada:** esqueleto novo do Laravel 13 com a Fase 2 portada para ele.
  - A favor: estrutura enxuta e moderna.
  - Contra: diff gigante e perda da relação com o commit inicial. Rejeitada; pode virar uma change própria no futuro, se fizer falta.
- **Plano B (se não aprovar):** ficar no Laravel 10 e usar `inertiajs/inertia-laravel` 2.x. As outras decisões não mudam.
- **Ajuste durante o apply (aprovado o upgrade):** o Composer recusa instalar qualquer Laravel 11.x, por avisos de segurança (versão fora de suporte). Instalar as versões intermediárias exigiria desligar essa proteção, então o salto passou a ser direto 10 → 13 num único `composer update`. Antes do salto, as mudanças dos guias 11, 12 e 13 que afetam este projeto são aplicadas no código. Os testes da Fase 2 são a rede de segurança, e `composer audit` limpo é critério de pronto.

### D2. Inertia 3 + Vue 3 instalados à mão, com o Breeze só como referência

- `breeze:install vue` traria Tailwind, cadastro, recuperação de senha e perfil, tudo fora do escopo, que teria de ser apagado em seguida. Por isso o Inertia entra com:
  - `inertiajs/inertia-laravel` ^3, `@inertiajs/vue3` ^3 e `@vitejs/plugin-vue`;
  - a view raiz `resources/views/app.blade.php`;
  - o middleware `HandleInertiaRequests`, que compartilha só `auth.usuario.nome`, `flash.sucesso`/`flash.erro` e `app.nome`.
- **Organização:** páginas em `resources/js/pages/<Area>/Index.vue`; layouts em `resources/js/layouts` (`PainelLayout`, `VisitanteLayout`); componentes em `resources/js/components`. As pastas ficam em minúsculas porque esse é o padrão do Inertia 3 (`config/inertia.php`) e dos starter kits atuais.
- **Sem Ziggy:** os links do painel são poucos e fixos, então um mapa de URLs em `resources/js/navegacao.ts` basta. Reavaliar quando houver muitas rotas com parâmetros.

### D3. TypeScript com checagem no build

- `npm run build` executa `vue-tsc --noEmit && vite build`.
- A versão do `typescript` fica fixada em `~6.0`. Confirmado no apply: o `vue-tsc` 3.3 não roda com o TypeScript 7 (versão nativa sem a API JS que o `vue-tsc` usa).
- Os tipos das props compartilhadas ficam em `resources/js/types/inertia.d.ts`.

### D4. CSS próprio com tokens em variáveis CSS, sem Tailwind

- **Onde ficam:** os tokens moram em `resources/css/tokens.css`, com `:root` para o tema claro e `[data-theme="dark"]` para o escuro; os componentes usam `<style scoped>` consumindo só essas variáveis.
- **Por quê:** a base visual é pequena, e CSS puro mantém o projeto com menos dependências e fácil de ler. O teste de contraste lê o próprio `tokens.css` (D9).
- **Alternativa considerada:** Tailwind 4, usado pelos starter kits. Rejeitada para evitar mais uma camada de configuração e de nomes de classe.

### D5. Tokens extraídos da imagem

Valores iniciais, com contraste já calculado (todos os pares de texto ≥ 4,5:1, bordas de controle e foco ≥ 3:1).

| Token | Claro | Escuro | Uso na imagem |
|---|---|---|---|
| `--cor-fundo` | `#F3F3EF` | `#111513` | área atrás dos cards |
| `--cor-superficie` | `#FFFFFF` | `#1A1F1D` | cards, sidebar |
| `--cor-superficie-suave` | `#EDF1EF` | `#232A27` | botão "See Details", abas inativas |
| `--cor-borda` | `#E2E4DF` | `#2C3431` | divisórias (decorativas) |
| `--cor-borda-controle` | `#8A918D` | `#7D8783` | contorno de campos (3,2:1 / 4,5:1) |
| `--cor-texto` | `#1B1F1D` | `#ECEFED` | títulos e valores |
| `--cor-texto-suave` | `#5A625E` | `#A9B2AE` | rótulos, datas (a imagem usa um cinza mais claro, que reprovaria no AA) |
| `--cor-primaria` / `--cor-sobre-primaria` | `#FFC83D` / `#1B1F1D` | igual | botão "Pay Bills", item ativo da sidebar |
| `--cor-marca` / `--cor-sobre-marca` | `#1D6B5F` / `#FFFFFF` | `#5CC2AE` / `#0E1A17` | avatar de iniciais, aba ativa |
| `--cor-foco` | `#1D6B5F` | `#5CC2AE` | contorno de foco |
| sucesso (fundo/texto) | `#DDF3E5` / `#17603A` | `#163826` / `#8EE0B0` | selo "Ready" → "Paga" |
| atenção | `#FFF1CC` / `#6E4A00` | `#3A2E0C` / `#FFD873` | selo "In Progress" → "Vence hoje" |
| informação | `#E4EAF8` / `#2F4377` | `#1F2A47` / `#AFC2F5` | selo "Completed" → "A vencer" |
| perigo | `#FCE3E1` / `#9C2318` | `#45201C` / `#FFADA5` | não existe na imagem → "Atrasada" |
| neutro | `#ECEEEC` / `#3F4642` | `#2A302D` / `#CDD3D0` | selos sem urgência |

- **Tipografia:** Inter Variable empacotada via `@fontsource-variable/inter`, sem requisição a CDN externa.
  - Escala: 12 / 14 / 16 (corpo) / 18 / 20 / 24 / 30 px. A imagem usa corpo de 14 px; o corpo foi subido para 16 px por legibilidade, e 12 px fica só em textos auxiliares.
  - Pesos 400 / 500 / 600 / 700, altura de linha 1,5.
- **Espaçamento:** base 4 px (4, 8, 12, 16, 20, 24, 32, 40, 48).
- **Raios:** 6 px (selos), 10 px (botões, campos, itens de navegação), 16 px (cards), 999 px (pílulas).
- **Sombra:** cards quase planos, `0 1px 2px rgb(0 0 0 / 0.04)`.
- **Layout:** sidebar de 248 px; conteúdo com no máximo 1200 px; pontos de quebra em 640 e 1024 px.

### D6. Tema sem piscar

- Um script inline mínimo no `<head>` do `app.blade.php` lê `localStorage['caderneta.tema']` (`claro` | `escuro` | `auto`) e aplica `data-theme` antes de qualquer CSS ser pintado. Em `auto`, segue `prefers-color-scheme`.
- O composable `useTema()` troca, salva e reage a mudanças do sistema. O acesso ao `localStorage` fica protegido por `try/catch`.
- `color-scheme` é declarado para que os controles nativos acompanhem o tema.

### D7. Casca do painel

- **Itens de navegação:** definidos uma única vez em `navegacao.ts` (rótulo, URL, ícone `@lucide/vue`). A sidebar e a barra inferior leem dessa lista.
- **Sidebar:** aparece a partir de 1024 px.
- **Barra inferior (abaixo de 1024 px):**
  - 4 áreas mais "Mais";
  - "Mais" é um botão com `aria-expanded` que abre um menu (Lembretes, Configurações, tema, Sair), com Esc e clique fora fechando e o foco voltando ao botão;
  - o conteúdo ganha `padding-bottom` igual à altura da barra mais `env(safe-area-inset-bottom)`.
- **Títulos e foco:**
  - o título da aba vem do `<Head>` do Inertia, com `title: (t) => \`${t} · Caderneta\``;
  - a cada evento `navigate` do Inertia, o foco vai para o `h1` (`tabindex="-1"`), e uma região `aria-live` anuncia o título.
- **Data do cabeçalho:** `Intl.DateTimeFormat('pt-BR', { dateStyle: 'full', timeZone: 'America/Fortaleza' })`, que gera "domingo, 25 de outubro de 2026" independentemente do fuso do aparelho.

### D8. Autenticação mínima, no padrão do Breeze

- **Login:**
  - `LoginController` com `LoginRequest`: validação, `RateLimiter` com chave e-mail+IP e 5 tentativas por minuto, `Auth::attempt($credenciais, $manterConectado)`, `session()->regenerate()` e `redirect()->intended('/painel')`;
  - rotas `GET|POST /entrar` com `guest` e `POST /sair` com `auth`;
  - o middleware `Authenticate` redireciona para `/entrar`.
- **Usuário:** o comando `caderneta:usuario` pergunta nome e e-mail (aceita também `--nome` e `--email`), lê a senha com `secret()`, valida no mínimo 10 caracteres e grava na primeira linha de `users`, criando ou atualizando; nunca cria uma segunda.
- A tabela `users` padrão é mantida (colunas em inglês, por ser do framework).

### D9. Testes

- **PHP:**
  - testes de feature com as asserções de Inertia (`assertInertia`) para login, limite, manter conectado, sair, redirecionamentos, comando do usuário, 404 de `/register` e `/api/user`, páginas de erro e acesso público a `/webhooks/asaas` e `/p/{token}`;
  - os 24 testes da Fase 2 rodam sem alteração.
- **Front:**
  - Vitest com `jsdom` e `@vue/test-utils`;
  - o helper `semViolacoesDeA11y(el)` roda `axe-core` direto, porque o `vitest-axe` está parado na 0.1.0;
  - o `axe` não calcula contraste no `jsdom`, então um teste próprio lê `tokens.css`, extrai os pares definidos e verifica as razões da spec `sistema-visual` nos dois temas.

### D10. Ambiente local e publicação

- **Docker:**
  - `php:8.3-fpm`;
  - banco `mysql:8.4` (troca de uma linha se a hospedagem for MariaDB);
  - sai o redis, que não é usado (cache e sessão em arquivo/banco).
- **Node 22 no host:** `npm run dev` usa o arquivo `public/hot` do laravel-vite-plugin; a página servida pelo nginx do Docker carrega os assets de `localhost:5173`.
- **Deploy (só documentação nesta change):**
  - na máquina local: `npm ci && npm run build` e `composer install --no-dev --optimize-autoloader`;
  - enviar o código, incluindo `public/build/` e `vendor/`, que não estão no git;
  - no servidor: `php artisan migrate --force`, `config:cache`, `route:cache` e `view:cache`;
  - a linha única do cron.
- O README cobre as duas formas comuns de apontar o domínio para `public/` na Hostinger, marcando a confirmar.

## Risks / Trade-offs

- **[Quebra no upgrade de três versões]** → um salto por commit, suíte verde a cada salto, guias oficiais lidos antes; a Fase 2 tem testes de webhook, geração e página.
- **[TypeScript 7 incompatível com `vue-tsc`]** → fixar a versão suportada (D3) e registrar no `package.json`.
- **[Exemplos do Inertia na internet ainda são da v2]** → seguir a documentação oficial da v3; a casca fica coberta por testes.
- **[Hospedagem sem SSH ou sem como apontar para `public/`]** → o README traz alternativa por `.htaccess`; a confirmação fica para a change `backup-e-deploy`.
- **[CSS próprio cresce sem disciplina]** → componentes só consomem tokens, e o teste de contraste falha se um par de tokens sair do AA.
- **[`jsdom` não testa layout real (larguras, sobreposição)]** → verificação manual registrada nas tarefas (320 px, 375 px, 1280 px, zoom de 200%, só teclado).

## Migration Plan

1. Tudo acontece na branch `change/base-arquitetura`, em commits pequenos (um por salto de versão e por grupo de tarefas).
2. Ainda não há produção, então não há migração de dados; as migrations existentes não mudam.
3. Para quem já roda o projeto localmente: recriar os containers (`docker-compose build`), rodar `composer install`, `npm ci` e `php artisan caderneta:usuario`.
4. Rollback: reverter o merge do PR na `main`.

## Open Questions

Nenhuma destas muda specs, abordagem ou tarefas; afetam só detalhes do README e uma linha do Docker:

- Na Hostinger Single: versão do PHP disponível, motor e versão do banco (MySQL ou MariaDB), acesso SSH e se o domínio pode apontar direto para `public/`.
- Nome do usuário a mostrar na sidebar (vem do comando `caderneta:usuario`; não precisa de decisão agora).
