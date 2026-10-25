# Tasks

## 1. Atualização do Laravel para 13 / PHP 8.3 (só após aprovação; se recusada, aplicar o plano B do design D1)

- [x] 1.1 Atualizar o Docker para `php:8.3-fpm` e `mysql:8.4`, remover o serviço redis sem uso e fixar `config.platform.php = 8.3.0` no `composer.json`. Pronto quando `docker-compose exec app php -v` mostra 8.3 e os 24 testes da Fase 2 passam.
- [x] 1.2 Aplicar no código as mudanças dos guias 10→11, 11→12 e 12→13 que afetam este projeto (dependências, Carbon 3, CSRF `PreventRequestForgery`, `serializable_classes`/`serialization`, PHPUnit 12), mantendo a estrutura atual de pastas. O salto é direto, porque o Composer bloqueia o Laravel 11 por avisos de segurança (ver D1). Pronto quando a lista do que se aplicou está na mensagem do commit.
- [x] 1.3 Subir direto para Laravel 13 em um único `composer update`. Pronto quando `php artisan about` mostra 13.x e os 24 testes da Fase 2 passam.
- [x] 1.4 Conferir segurança e avisos após o salto. Pronto quando `composer audit` não aponta avisos e a suíte roda sem deprecações do PHPUnit.
- [x] 1.5 Remover `laravel/sanctum`, `routes/api.php` e o carregamento das rotas de API no `RouteServiceProvider`. Pronto quando um teste confirma que `GET /api/user` responde 404 e a suíte toda passa.

## 2. Inertia + Vue 3 + TypeScript

- [x] 2.1 Instalar `inertiajs/inertia-laravel` ^3, criar `resources/views/app.blade.php` e o middleware `HandleInertiaRequests`, compartilhando apenas `auth.usuario.nome`, `flash` e `app.nome`. Pronto quando um teste de feature com `assertInertia` mostra essas props compartilhadas, e só elas.
- [x] 2.2 Instalar `vue`, `@inertiajs/vue3`, `@vitejs/plugin-vue`, `typescript` (versão compatível com o `vue-tsc`, ver D3), `vue-tsc` e `@fontsource-variable/inter`; criar `resources/js/app.ts`, `tsconfig.json` e `resources/js/types/inertia.d.ts`; atualizar `vite.config` e o script `build` para `vue-tsc --noEmit && vite build`. Pronto quando `npm run build` termina sem erro de tipo e gera `public/build/manifest.json`.
- [x] 2.3 Configurar Vitest (`jsdom`, `@vue/test-utils`, `axe-core`) com o script `npm test` e o helper `semViolacoesDeA11y`. Pronto quando `npm test` roda um teste de exemplo de componente e o helper reprova um botão sem nome acessível.

## 3. Tokens, tipografia e tema

- [x] 3.1 Criar `resources/css/tokens.css` com os tokens do design D5 (claro em `:root`, escuro em `[data-theme="dark"]`) e `resources/css/base.css` (reset leve, Inter, foco visível com `--cor-foco`, `prefers-reduced-motion`, `color-scheme`). Pronto quando o teste de contraste, que lê o `tokens.css`, passa nos dois temas e falha ao trocar `--cor-texto-suave` por um cinza abaixo de 4,5:1.
- [x] 3.2 Adicionar o script inline de tema no `<head>` do `app.blade.php` e o composable `useTema()` (Claro, Escuro, Automático, salvo em `localStorage`, com `try/catch`). Pronto quando os testes Vitest cobrem: preferência do sistema sem escolha salva, escolha manual persistida, reação à mudança do sistema em modo Automático e `localStorage` indisponível sem erro; e um teste PHP confirma que o script vem antes do CSS no HTML.

## 4. Componentes base

- [x] 4.1 Criar `Botao` (variantes primária, secundária e marca; estado carregando com `aria-busy`/`aria-disabled`, bloqueando novo clique). Pronto quando os testes cobrem variantes, Enter/Espaço, o bloqueio no estado carregando e passam no `axe`.
- [x] 4.2 Criar `Card`, `AvatarIniciais` (iniciais com nome completo acessível) e `SeloSituacao` (texto + ícone `aria-hidden` para paga, parcial, atrasada, vence hoje, vence em breve, a vencer e neutro). Pronto quando os testes confirmam o texto em pt-BR de cada variante e o ícone oculto de leitores de tela.
- [x] 4.3 Criar `CampoTexto` e `CampoBusca` (rótulo visível associado, erro com `aria-invalid` e `aria-describedby`) e `AbasFiltro` (grupo de botões com `aria-pressed`, como as pílulas da imagem). Pronto quando os testes verificam a associação rótulo/erro, a seleção por teclado e passam no `axe`.
- [x] 4.4 Criar `TabelaResponsiva` (cabeçalhos `th scope="col"`; abaixo de 640 px vira cartões com rótulo de cada valor) e os estados `EstadoCarregando` (`aria-busy`), `EstadoVazio` e `EstadoErro` (botão "Tentar de novo", região `aria-live`). Pronto quando os testes cobrem a marcação semântica, a emissão do evento de nova tentativa e passam no `axe`.

## 5. Autenticação do usuário único

- [x] 5.1 Criar o comando `caderneta:usuario` (pergunta nome e e-mail, senha via `secret`, mínimo de 10 caracteres, cria ou atualiza a única linha de `users`). Pronto quando os testes cobrem primeira execução, redefinição mantendo 1 usuário e recusa de senha curta.
- [x] 5.2 Criar `LoginController` e `LoginRequest` (validação em pt-BR, limite de 5 tentativas por e-mail+IP, "Manter conectado", `regenerate`, `intended('/painel')`), as rotas `GET|POST /entrar` (`guest`) e `POST /sair` (`auth`), e ajustar os redirecionamentos de `Authenticate` e `RedirectIfAuthenticated`. Pronto quando os testes PHP cobrem todos os cenários da spec `autenticacao`, incluindo `GET /sair` sem efeito e 404 em `/register`, `/cadastro` e `/forgot-password`.
- [x] 5.3 Criar `VisitanteLayout` e a página `Entrar.vue` com os componentes base (rótulos, mensagem genérica de erro, foco no primeiro campo com erro, botão em estado carregando durante o envio). Pronto quando o teste Vitest cobre a exibição de erros, o foco e o `axe`, e o login funciona no navegador pelo Docker.

## 6. Casca do painel

- [x] 6.1 Criar `resources/js/navegacao.ts` (áreas, URLs, ícones), `PainelLayout` com sidebar (≥ 1024 px), barra inferior com menu "Mais" (< 1024 px), link "Pular para o conteúdo", troca de tema e "Sair". Pronto quando os testes Vitest cobrem `aria-current` na área atual, abrir/fechar "Mais" com Esc devolvendo o foco e o `axe` da casca.
- [x] 6.2 Criar `CabecalhoPagina` (único `h1` com `tabindex="-1"`, data por extenso em America/Fortaleza) e o foco no título a cada navegação do Inertia, com anúncio em região `aria-live`; título da aba "<Área> · Caderneta". Pronto quando um teste com data fixa mostra "domingo, 25 de outubro de 2026" e outro confirma o foco no `h1` após a navegação.
- [x] 6.3 Criar as rotas `/painel`, `/clientes`, `/emprestimos`, `/parcelas`, `/lembretes` e `/configuracoes` dentro de um grupo `auth`, cada uma com página provisória (título + estado vazio "Em construção"), e `/` redirecionando para `/painel`. Pronto quando os testes PHP confirmam o componente Inertia de cada rota para o usuário autenticado e o redirecionamento a `/entrar` para visitantes.

## 7. Páginas de erro

- [x] 7.1 Renderizar a página Inertia `Erro.vue` para 404, 419, 500 e 503 nas rotas do painel (sem detalhes técnicos fora de `local`), mantendo JSON em `/webhooks/asaas`. Pronto quando os testes cobrem 404 com link para o Painel, 419 com "Sua sessão expirou", 500 em produção sem stack trace e o webhook continuando a responder em JSON.

## 8. Rotas públicas preservadas

- [x] 8.1 Adicionar testes da spec `rotas-publicas`: webhook sem cookies nem CSRF responde 200/401 em JSON (sem 419 nem redirecionamento), `/p/{token}` abre sem sessão com `X-Robots-Tag` mesmo sem `public/build`, e uma rota do grupo do painel sem sessão vai para `/entrar`. Pronto quando esses testes e os 24 da Fase 2 passam.

## 9. Documentação

- [x] 9.1 Reescrever o `README.md`: requisitos (PHP 8.3, Composer, Node 22 só local, Docker), setup local, variáveis de ambiente (sem valores reais), criação do usuário, testes (`php artisan test`, `npm test`), compilação dos assets, publicação na Hostinger (build e `vendor` locais, envio sem git dos compilados, apontar o domínio para `public/`, comandos pós-deploy, linha única do cron) e a seção da Fase 2 (Asaas sandbox, túnel, webhook) atualizada. Pronto quando os comandos de setup do README, seguidos num clone limpo, sobem o projeto e o login funciona.

## 10. Verificação integrada

- [ ] 10.1 Rodar `php artisan test`, `npm test`, `npm run build` e `openspec validate base-arquitetura --strict`. Pronto quando tudo passa sem falhas.
- [ ] 10.2 Verificar manualmente no navegador, nos dois temas: 320, 375 e 1280 px sem rolagem horizontal, zoom de texto em 200%, navegação só por teclado (pular para o conteúdo, foco visível, menu "Mais") e leitura das áreas por leitor de tela. Pronto quando o resultado está registrado na descrição do PR.

## Workflow follow-up

- Rodar `/opsx:verify` e mostrar o resultado antes de arquivar.
- Abrir o Pull Request de `change/base-arquitetura` para `main` e pedir revisão antes do merge.
- Rodar `/opsx:archive` no mesmo PR, depois da revisão.
