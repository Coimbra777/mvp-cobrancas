# navegacao-painel Specification

## Purpose
Define a casca do painel da Caderneta: como o Dario se orienta e navega entre as áreas no desktop e no celular, com cabeçalho, páginas provisórias e páginas de erro em pt-BR.

## Requirements

### Requirement: Áreas do painel
O painel SHALL oferecer, nesta ordem, as áreas Painel (`/painel`), Clientes (`/clientes`), Empréstimos (`/emprestimos`), Parcelas (`/parcelas`), Lembretes (`/lembretes`) e Configurações (`/configuracoes`). A raiz `/` SHALL levar a `/painel`.

#### Scenario: Raiz do site
- **WHEN** o usuário autenticado abre `/`
- **THEN** o sistema redireciona para `/painel`

#### Scenario: Áreas ainda não construídas
- **WHEN** o usuário abre uma área cuja funcionalidade ainda não existe
- **THEN** a página mostra o título da área e um estado vazio com o texto "Em construção", sem erro

### Requirement: Navegação lateral no desktop
Em telas a partir de 1024 px de largura, o painel SHALL mostrar uma barra lateral fixa com a marca, os links das áreas com ícone e texto, a identificação do usuário (nome) e a ação "Sair", destacando a área atual.

#### Scenario: Área atual destacada
- **WHEN** o usuário está em `/clientes` com tela de 1280 px
- **THEN** o link "Clientes" aparece destacado e marcado com `aria-current="page"`, e os demais links não

#### Scenario: Sair pela barra lateral
- **WHEN** o usuário ativa "Sair" na barra lateral
- **THEN** a sessão é encerrada conforme a capability `autenticacao`

### Requirement: Navegação no celular
Em telas com menos de 1024 px, o painel SHALL substituir a barra lateral por uma barra inferior fixa com Painel, Clientes, Empréstimos, Parcelas e um item "Mais" que abre Lembretes, Configurações, troca de tema e "Sair". Cada alvo de toque SHALL ter no mínimo 44×44 px.

#### Scenario: Barra inferior em tela pequena
- **WHEN** o painel é aberto com 375 px de largura
- **THEN** a barra lateral não aparece, a barra inferior aparece com os 5 itens e a área atual está marcada com `aria-current="page"`

#### Scenario: Menu "Mais" por teclado
- **WHEN** o usuário abre "Mais" e pressiona Esc
- **THEN** o menu fecha e o foco volta para o botão "Mais"

#### Scenario: Conteúdo não fica escondido
- **WHEN** o usuário rola uma página até o fim no celular
- **THEN** o último conteúdo fica visível acima da barra inferior

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
- **WHEN** o usuário ativa o link "Parcelas"
- **THEN** a página de Parcelas carrega e o foco vai para o título "Parcelas", que é anunciado por leitores de tela

### Requirement: Páginas de erro
O sistema SHALL mostrar páginas de erro em pt-BR, no mesmo estilo do painel, para página não encontrada (404), sessão expirada (419) e erro interno (500), sempre com um caminho de volta. Detalhes técnicos MUST NOT aparecer em produção.

#### Scenario: Página inexistente
- **WHEN** o usuário autenticado abre `/nao-existe`
- **THEN** o sistema responde 404 com "Página não encontrada" e um link para o Painel

#### Scenario: Sessão expirada ao enviar formulário
- **WHEN** um formulário é enviado com token CSRF expirado
- **THEN** o sistema mostra "Sua sessão expirou" com a opção de recarregar a página, em vez de uma tela técnica

#### Scenario: Erro interno em produção
- **WHEN** ocorre uma exceção não tratada com o ambiente em produção
- **THEN** a página mostra "Algo deu errado" sem stack trace e o erro é registrado no log
