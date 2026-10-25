# navegacao-painel Specification

## Purpose
Define a casca do painel da Caderneta: como o Dario se orienta e navega entre Clientes, Empréstimos e Cobranças no desktop e no celular, com cabeçalho e textos em pt-BR.

## Requirements

### Requirement: Navegação lateral no desktop
Em telas a partir de 1024 px de largura, o painel SHALL mostrar uma barra lateral fixa com a marca, os links das áreas com ícone e texto, a identificação do usuário (nome) e a ação "Sair", destacando a área atual.

#### Scenario: Área atual destacada
- **WHEN** o usuário está em `/clientes` com tela de 1280 px
- **THEN** o link "Clientes" aparece destacado e marcado com `aria-current="page"`, e os demais links não

#### Scenario: Sair pela barra lateral
- **WHEN** o usuário ativa "Sair" na barra lateral
- **THEN** a sessão é encerrada conforme a capability `autenticacao`

### Requirement: Cabeçalho da página
Cada página do painel SHALL ter um cabeçalho com o título da área como único `h1` e a data de hoje por extenso em pt-BR no fuso America/Fortaleza. O título da aba do navegador SHALL seguir o padrão "<Área> · Caderneta".

#### Scenario: Data por extenso
- **WHEN** o usuário abre o painel em 25/10/2026 no horário de Fortaleza
- **THEN** o cabeçalho mostra "domingo, 25 de outubro de 2026"

#### Scenario: Título da aba
- **WHEN** o usuário abre `/emprestimos`
- **THEN** o título da aba é "Empréstimos · Caderneta"

### Requirement: Navegação por teclado na casca
A casca SHALL ter um link "Pular para o conteúdo" como primeiro item focável, e a ordem de foco SHALL seguir a ordem visual: navegação, cabeçalho, conteúdo. Ao trocar de página, o foco SHALL ir para o título da nova página.

#### Scenario: Pular para o conteúdo
- **WHEN** o usuário pressiona Tab ao abrir uma página e ativa "Pular para o conteúdo"
- **THEN** o foco vai para a região principal da página

#### Scenario: Foco após navegar
- **WHEN** o usuário ativa o link "Cobranças"
- **THEN** a página de Cobranças carrega e o foco vai para o título "Cobranças", que é anunciado por leitores de tela

### Requirement: Áreas do MVP
O painel SHALL oferecer as áreas Clientes (`/clientes`), Empréstimos (`/emprestimos`) e Cobranças (`/cobrancas`), e a raiz `/` SHALL levar a `/cobrancas`.

#### Scenario: Raiz do site
- **WHEN** o usuário autenticado abre `/`
- **THEN** o sistema redireciona para `/cobrancas`

### Requirement: Barra inferior no celular
Em telas com menos de 1024 px, o painel SHALL substituir a barra lateral por uma barra inferior fixa com Clientes, Empréstimos, Cobranças e "Sair", com alvos de toque de no mínimo 44×44 px e sem esconder o fim do conteúdo.

#### Scenario: Barra inferior em tela pequena
- **WHEN** o painel é aberto com 375 px de largura
- **THEN** a barra inferior mostra os 4 itens e a área atual está marcada com `aria-current="page"`
