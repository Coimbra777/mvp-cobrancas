# sistema-visual Specification

## Purpose
Estabelece a linguagem visual da Caderneta, derivada da imagem de referência: tokens, tema claro/escuro, componentes base e estados de interface, todos atendendo WCAG 2.2 AA.

## Requirements

### Requirement: Tokens de design
A interface SHALL usar um conjunto único de tokens de cor, tipografia, espaçamento, raio e sombra, definido para os temas claro e escuro. Componentes MUST NOT usar valores de cor fora dos tokens.

#### Scenario: Troca de tema muda só os tokens
- **WHEN** o tema muda de claro para escuro
- **THEN** cores de fundo, superfície, texto, borda e selos mudam, e espaçamento, tipografia e raios continuam iguais

### Requirement: Contraste mínimo
Em ambos os temas, texto normal SHALL ter contraste de pelo menos 4,5:1 com o fundo. Texto grande, ícones informativos e bordas de controles SHALL ter pelo menos 3:1. O amarelo da marca SHALL ser usado só como fundo, com texto escuro.

#### Scenario: Pares de tokens verificados
- **WHEN** a verificação automática calcula o contraste de cada par texto/fundo definido nos tokens
- **THEN** todos os pares de texto normal ficam em 4,5:1 ou mais e os de componentes em 3:1 ou mais, nos dois temas

### Requirement: Tema claro e escuro
O painel SHALL seguir a preferência de tema do sistema operacional por padrão e SHALL oferecer uma troca manual (Claro, Escuro, Automático) que fica salva no navegador. A página MUST NOT piscar no tema errado ao carregar.

#### Scenario: Preferência do sistema
- **WHEN** o sistema operacional está em modo escuro e não há escolha salva
- **THEN** o painel abre no tema escuro

#### Scenario: Escolha manual persiste
- **WHEN** o usuário escolhe "Claro" e recarrega a página
- **THEN** o painel abre no tema claro desde o primeiro quadro, mesmo com o sistema em modo escuro

### Requirement: Foco visível
Todo elemento interativo SHALL mostrar um indicador de foco visível ao receber foco por teclado, com contraste de pelo menos 3:1, e o elemento focado MUST NOT ficar totalmente encoberto por barras fixas.

#### Scenario: Navegação por Tab
- **WHEN** o usuário navega com Tab pelos links, botões e campos de uma página
- **THEN** cada elemento focado mostra um contorno visível e fica inteiramente à vista

### Requirement: Situação nunca só por cor
Selos de situação SHALL comunicar o estado por texto e ícone, além da cor, com texto em pt-BR.

#### Scenario: Selo de situação
- **WHEN** um selo com a variante "atrasada" é exibido
- **THEN** ele mostra o texto "Atrasada" e um ícone, e o ícone é ignorado por leitores de tela

### Requirement: Botões
O botão SHALL ter as variantes primária (amarela), secundária (neutra) e de marca (verde-petróleo), ser acionável por Enter e Espaço e ter um estado "carregando" que impede novo envio e é anunciado a leitores de tela.

#### Scenario: Envio em andamento
- **WHEN** o usuário aciona um botão em estado "carregando"
- **THEN** nada acontece, o botão mantém o rótulo visível e tem `aria-busy="true"` e `aria-disabled="true"`

### Requirement: Campos de formulário
Todo campo SHALL ter rótulo visível associado. Mensagens de erro SHALL aparecer junto ao campo, ser associadas a ele e anunciadas a leitores de tela; o campo com erro SHALL ser marcado como inválido.

#### Scenario: Erro de validação
- **WHEN** o envio de um formulário volta com erro no campo "E-mail"
- **THEN** o campo tem `aria-invalid="true"`, a mensagem aparece abaixo dele, está ligada por `aria-describedby` e o foco vai para o primeiro campo com erro

### Requirement: Estados de carregando, vazio e erro
Listas e cards SHALL ter estados de carregando, vazio e erro no mesmo estilo visual. O estado de erro SHALL oferecer uma ação de tentar de novo. Mudanças de estado SHALL ser anunciadas sem mover o foco.

#### Scenario: Lista vazia
- **WHEN** uma lista não tem itens
- **THEN** aparece um estado vazio com ícone, título e texto explicativo, em vez de uma área em branco

#### Scenario: Erro com nova tentativa
- **WHEN** o carregamento de uma lista falha
- **THEN** aparece a mensagem de erro com o botão "Tentar de novo", anunciada por uma região `aria-live`

### Requirement: Tabela responsiva
Tabelas SHALL usar cabeçalhos de coluna semânticos e, em telas menores que 640 px, SHALL se reorganizar em cartões que mantêm o rótulo de cada valor, sem rolagem horizontal da página.

#### Scenario: Tabela no celular
- **WHEN** uma tabela é exibida com 375 px de largura
- **THEN** cada linha vira um cartão com pares "rótulo: valor" e a página não rola na horizontal

### Requirement: Layout adaptável e legível
O painel SHALL funcionar a partir de 320 px de largura sem rolagem horizontal, SHALL continuar utilizável com zoom de texto de 200% e SHALL respeitar a preferência de movimento reduzido.

#### Scenario: Tela de 320 px
- **WHEN** o painel é aberto com 320 px de largura
- **THEN** todo o conteúdo cabe na largura e nada é cortado

#### Scenario: Movimento reduzido
- **WHEN** o sistema operacional pede movimento reduzido
- **THEN** transições e animações são desativadas ou reduzidas a troca instantânea
