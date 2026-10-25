# Spec Delta

## MODIFIED Requirements

### Requirement: Entrar com e-mail e senha
O sistema SHALL autenticar o usuário por e-mail e senha em uma tela de entrada em pt-BR e, em caso de sucesso, abrir uma sessão e levá-lo às Cobranças ou à página protegida que ele tentou abrir.

#### Scenario: Credenciais corretas
- **WHEN** o usuário informa e-mail e senha corretos em `/entrar`
- **THEN** o sistema abre a sessão, renova o identificador de sessão e redireciona para `/cobrancas`

#### Scenario: Volta para a página pedida
- **WHEN** um visitante sem sessão abre `/clientes`, é levado a `/entrar` e entra com sucesso
- **THEN** o sistema o redireciona para `/clientes`

#### Scenario: Credenciais erradas
- **WHEN** o e-mail não existe ou a senha está errada
- **THEN** o sistema mantém o visitante em `/entrar`, mostra a mensagem genérica "E-mail ou senha incorretos." sem indicar qual campo errou, e não abre sessão

#### Scenario: Campos obrigatórios
- **WHEN** o formulário é enviado com e-mail vazio, e-mail inválido ou senha vazia
- **THEN** o sistema mostra a mensagem de validação em pt-BR junto ao campo correspondente

### Requirement: Páginas do painel exigem sessão
O sistema SHALL exigir sessão autenticada em todas as páginas do painel e SHALL redirecionar visitantes sem sessão para `/entrar`.

#### Scenario: Visitante abre o painel
- **WHEN** um visitante sem sessão abre `/cobrancas`
- **THEN** o sistema redireciona para `/entrar`

#### Scenario: Usuário autenticado abre a tela de entrada
- **WHEN** um usuário já autenticado abre `/entrar`
- **THEN** o sistema redireciona para `/cobrancas`
