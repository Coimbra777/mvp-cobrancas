# Spec Delta

## MODIFIED Requirements

### Requirement: Navegação por teclado na casca
A casca SHALL ter um link "Pular para o conteúdo" como primeiro item focável, e a ordem de foco SHALL seguir a ordem visual: navegação, cabeçalho, conteúdo. Ao trocar de página, o foco SHALL ir para o título da nova página.

#### Scenario: Pular para o conteúdo
- **WHEN** o usuário pressiona Tab ao abrir uma página e ativa "Pular para o conteúdo"
- **THEN** o foco vai para a região principal da página

#### Scenario: Foco após navegar
- **WHEN** o usuário ativa o link "Cobranças"
- **THEN** a página de Cobranças carrega e o foco vai para o título "Cobranças", que é anunciado por leitores de tela

## REMOVED Requirements

### Requirement: Áreas do painel
**Reason**: O MVP de validação tem só três áreas; Painel, Parcelas, Lembretes e Configurações saem, junto com a página provisória "Em construção".
**Migration**: Ver o requisito "Áreas do MVP".

### Requirement: Navegação no celular
**Reason**: Com três áreas não há mais menu "Mais" nem troca de tema.
**Migration**: Ver o requisito "Barra inferior no celular".

### Requirement: Páginas de erro
**Reason**: Páginas de erro customizadas não servem à validação do MVP.
**Migration**: Ficam as páginas de erro padrão do Laravel.

## ADDED Requirements

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
