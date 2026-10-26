# Spec Delta

## MODIFIED Requirements

### Requirement: Áreas do MVP
O painel SHALL oferecer as áreas Clientes (`/clientes`), Empréstimos (`/emprestimos`) e Cobranças (`/cobrancas`) e, no modo manual, Comprovantes (`/comprovantes`) com a contagem de pendentes. A raiz `/` SHALL levar a `/cobrancas`.

#### Scenario: Raiz do site
- **WHEN** o usuário autenticado abre `/`
- **THEN** o sistema redireciona para `/cobrancas`

#### Scenario: Comprovantes no modo manual
- **WHEN** o modo é manual e há 2 comprovantes pendentes
- **THEN** a navegação mostra "Comprovantes" com a contagem 2; no modo asaas a área não aparece e `/comprovantes` responde 404

### Requirement: Barra inferior no celular
Em telas com menos de 1024 px, o painel SHALL substituir a barra lateral por uma barra inferior fixa com as áreas do modo atual e "Sair", com alvos de toque de no mínimo 44×44 px e sem esconder o fim do conteúdo.

#### Scenario: Barra inferior em tela pequena
- **WHEN** o painel é aberto com 375 px de largura
- **THEN** a barra inferior mostra as áreas do modo atual e "Sair", e a área atual está marcada com `aria-current="page"`
