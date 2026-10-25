# Proposal

## Why

O repositório hoje é o boilerplate Laravel 10 com o trabalho da Fase 2 (QR Code Pix, webhook e página `/p/{token}`). Ele não tem painel, login nem front-end. Antes de construir cadastros, pagamentos e lembretes, a Caderneta precisa de uma base comum: uma versão do Laravel com suporte, o Inertia + Vue 3 + TypeScript, o acesso do Dario, a casca do painel seguindo o layout de referência e um jeito documentado de compilar e publicar numa hospedagem compartilhada sem Node.

O Laravel 10 está sem correções de segurança. O Inertia 3, versão atual, exige Laravel 11.35 ou mais novo. Por isso a atualização vem primeiro, enquanto o código próprio ainda é pequeno.

## What Changes

- **Atualização do Laravel (depende da sua aprovação).** Recomendação: **Laravel 13 com PHP 8.3**.
  - **Avaliação das versões:**
    - **Laravel 10:** sem correções.
    - **Laravel 12:** sem correções de bugs desde 13/08/2026; segurança só até 24/02/2027.
    - **Laravel 13:** exige PHP 8.3; segurança até o 1º trimestre de 2028.
    - **Hostinger:** PHP 8.3 é o padrão em sites novos, com opções até 8.5.
  - **Caminho:** atualização no lugar, versão a versão (10→11→12→13), mantendo a estrutura atual de pastas, que continua suportada. Cada salto é um commit, com os testes da Fase 2 verdes.
  - **Plano B, se não aprovar:** ficar no Laravel 10 com Inertia 2. O resto desta change continua igual, com o risco de segurança registrado.
  - **BREAKING** para o ambiente local: Docker com PHP 8.3 e banco local atualizado. O Sanctum sai, porque não haverá API pública.
- **Inertia + Vue 3 + TypeScript** instalados com os adaptadores oficiais; o Breeze (stack Inertia + Vue) serve só de referência. Vite compila para `public/build`.
- **Login do usuário único** com sessão padrão do Laravel:
  - entrar, sair e "manter conectado";
  - limite de tentativas;
  - usuário criado ou redefinido por comando artisan;
  - sem cadastro público e sem recuperação de senha por e-mail.
- **Casca do painel a partir da imagem de referência:**
  - sidebar no desktop e barra de navegação inferior no celular;
  - cabeçalho com título e data;
  - páginas provisórias para Painel, Clientes, Empréstimos, Parcelas, Lembretes e Configurações;
  - páginas de erro (404/419/500).
- **Sistema visual:**
  - tokens extraídos da imagem (cores, tipografia, espaçamento, raios) e tema claro/escuro;
  - componentes base: botão, card, selo de situação, abas-filtro, campo, busca, avatar de iniciais, tabela responsiva;
  - estados de carregando, vazio e erro;
  - WCAG 2.2 AA.
- **Rotas fora do Inertia preservadas:** `POST /webhooks/asaas` (fora do CSRF) e `/p/{token}` (sem login), sem mudança de comportamento.
- **README reescrito:** requisitos, setup local, variáveis de ambiente, testes, compilação dos assets e publicação na hospedagem (assets compilados sobem no deploy, não no git) e a linha única do cron.
- **Testes de base:**
  - PHPUnit: login, proteção de rotas, comando do usuário e os 24 testes da Fase 2 continuando verdes;
  - Vitest + Vue Test Utils: casca, navegação, tema, login, componentes, contraste dos tokens, checagem automática de acessibilidade.

## Capabilities

### New Capabilities
- `autenticacao`: acesso do único usuário (entrar, sair, sessão, limite de tentativas, criação/redefinição por comando, ausência de cadastro público e de API pública).
- `navegacao-painel`: casca do painel (sidebar no desktop, navegação no celular, cabeçalho, páginas provisórias, páginas de erro, textos em pt-BR).
- `sistema-visual`: tokens, tema claro/escuro, componentes base e estados de carregando/vazio/erro, com os requisitos de acessibilidade WCAG 2.2 AA.
- `rotas-publicas`: o que continua acessível sem login (webhook do Asaas fora do CSRF e página `/p/{token}`) e o que exige login (todo o resto).

### Modified Capabilities
- Nenhuma. Ainda não há specs. O comportamento detalhado da Fase 2 ganha spec própria na change `cobranca-pix-asaas`; aqui só garantimos que as duas rotas seguem públicas e funcionando.

## Impact

- **Código:** `composer.json` e configs (upgrade), `routes/web.php`, middlewares, novo `resources/js` (Pages, Layouts, Components, tokens), `resources/views/app.blade.php`, testes novos e README.
- **Dependências:**
  - entram: `inertiajs/inertia-laravel`, `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `typescript`, `vue-tsc`, `vitest`, `@vue/test-utils`, `jsdom`, `axe-core`, `@lucide/vue` e `@fontsource-variable/inter`;
  - sai: `laravel/sanctum`.
- **Ambiente:** Docker local com PHP 8.3. Node 22 só na máquina de desenvolvimento/CI, nunca no servidor.
- **Hospedagem:** pontos a confirmar no hPanel do plano Single:
  - versão do PHP;
  - motor e versão do banco (MySQL ou MariaDB);
  - acesso SSH;
  - como apontar o domínio para `public/`.

## Fora do escopo

- Cadastro de clientes e empréstimos, cálculos, parcelas, prévia e renegociação (`cadastros-e-emprestimos`).
- Baixa manual, juros de atraso, valor atualizado e o conteúdo real do painel (`pagamentos-e-painel`).
- Mudanças de comportamento na integração com o Asaas e no visual da página `/p/{token}` (`cobranca-pix-asaas`).
- Fila de lembretes e drivers de envio (`lembretes-fila-manual`).
- Backup automático, CI e a publicação real na Hostinger (`backup-e-deploy`); aqui entra só a documentação.
- Recuperação de senha por e-mail, múltiplos usuários e cadastro público.
