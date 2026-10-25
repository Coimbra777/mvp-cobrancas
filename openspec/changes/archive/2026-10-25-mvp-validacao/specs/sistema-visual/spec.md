# Spec Delta

## REMOVED Requirements

### Requirement: Tema claro e escuro
**Reason**: A troca manual de tema não serve à validação do MVP.
**Migration**: Ver o requisito "Tema automático".

### Requirement: Estados de carregando, vazio e erro
**Reason**: As páginas Inertia chegam com os dados prontos; não há carregamento nem erro de busca no navegador.
**Migration**: Ver o requisito "Estado vazio".

## ADDED Requirements

### Requirement: Tema automático
O painel SHALL seguir automaticamente o tema claro ou escuro do sistema operacional, sem troca manual e sem piscar no tema errado ao carregar.

#### Scenario: Sistema em modo escuro
- **WHEN** o sistema operacional está em modo escuro
- **THEN** o painel abre no tema escuro desde o primeiro quadro

### Requirement: Estado vazio
Listas sem itens SHALL mostrar um estado vazio com ícone, título e texto explicativo em vez de uma área em branco.

#### Scenario: Lista vazia
- **WHEN** uma lista não tem itens
- **THEN** aparece um estado vazio com ícone, título e texto explicativo
