# autenticacao Specification

## Purpose
Garante que só o Dario, único usuário da Caderneta, acesse o painel, por meio de sessão com e-mail e senha, sem cadastro público e sem API pública.

## Requirements

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

### Requirement: Limite de tentativas de entrada
O sistema SHALL bloquear temporariamente novas tentativas de entrada depois de 5 falhas seguidas para o mesmo e-mail e endereço IP, informando em quanto tempo será possível tentar de novo.

#### Scenario: Sexta tentativa bloqueada
- **WHEN** ocorrem 5 tentativas com senha errada para o mesmo e-mail e IP em menos de 1 minuto e uma sexta é enviada
- **THEN** o sistema recusa a tentativa sem verificar a senha e mostra "Muitas tentativas. Tente de novo em N segundos."

#### Scenario: Sucesso zera o contador
- **WHEN** o usuário entra com sucesso depois de falhas anteriores
- **THEN** o contador de falhas desse e-mail e IP volta a zero

### Requirement: Manter conectado
O sistema SHALL oferecer a opção "Manter conectado" que, quando marcada, mantém o usuário autenticado depois que a sessão do navegador expira.

#### Scenario: Opção marcada
- **WHEN** o usuário entra com "Manter conectado" marcado e a sessão expira
- **THEN** o próximo acesso continua autenticado sem pedir a senha

#### Scenario: Opção desmarcada
- **WHEN** o usuário entra sem marcar a opção e a sessão expira
- **THEN** o próximo acesso a uma página protegida leva a `/entrar`

### Requirement: Sair
O sistema SHALL encerrar a sessão quando o usuário escolher "Sair", invalidando a sessão e o token CSRF, e SHALL aceitar a saída apenas por requisição POST.

#### Scenario: Saída
- **WHEN** o usuário autenticado escolhe "Sair"
- **THEN** a sessão é invalidada, o token CSRF é renovado e ele é levado a `/entrar`

#### Scenario: Saída por GET não é aceita
- **WHEN** alguém faz `GET /sair`
- **THEN** o sistema não encerra a sessão

### Requirement: Páginas do painel exigem sessão
O sistema SHALL exigir sessão autenticada em todas as páginas do painel e SHALL redirecionar visitantes sem sessão para `/entrar`.

#### Scenario: Visitante abre o painel
- **WHEN** um visitante sem sessão abre `/cobrancas`
- **THEN** o sistema redireciona para `/entrar`

#### Scenario: Usuário autenticado abre a tela de entrada
- **WHEN** um usuário já autenticado abre `/entrar`
- **THEN** o sistema redireciona para `/cobrancas`

### Requirement: Usuário único criado por comando
O sistema SHALL permitir criar ou redefinir o único usuário apenas por um comando de terminal que recebe nome e e-mail e pede a senha sem exibi-la, exigindo no mínimo 10 caracteres. Rodar de novo SHALL atualizar o mesmo usuário, nunca criar um segundo.

#### Scenario: Primeira execução
- **WHEN** o comando é executado sem usuário cadastrado
- **THEN** o usuário é criado com a senha armazenada apenas como hash

#### Scenario: Redefinição
- **WHEN** o comando é executado com um usuário já cadastrado
- **THEN** o mesmo usuário recebe o novo nome, e-mail e senha e o total de usuários continua 1

#### Scenario: Senha curta
- **WHEN** a senha informada tem menos de 10 caracteres
- **THEN** o comando recusa e não altera nada

### Requirement: Sem cadastro público e sem API pública
O sistema MUST NOT expor rotas de cadastro de usuário, de recuperação de senha nem endpoints de API autenticados por token. O único endpoint de máquina SHALL ser o webhook do Asaas.

#### Scenario: Rota de cadastro inexistente
- **WHEN** alguém abre `/register`, `/cadastro` ou `/forgot-password`
- **THEN** o sistema responde 404

#### Scenario: Rotas de API inexistentes
- **WHEN** alguém faz uma requisição para `/api/user`
- **THEN** o sistema responde 404
