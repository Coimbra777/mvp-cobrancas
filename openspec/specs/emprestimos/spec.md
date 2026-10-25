# emprestimos Specification

## Purpose
Registra cada empréstimo e gera automaticamente as parcelas, com o cálculo de juros (simples ou composto) e os vencimentos mensais, para que a cobrança comece sem trabalho manual.

## Requirements

### Requirement: Cadastrar empréstimo
O sistema SHALL cadastrar um empréstimo com cliente, valor (maior que zero, em reais com até 2 casas), taxa mensal (de 0 a 100%, até 2 casas), método (simples ou composto), número de parcelas (1 a 60) e data do primeiro vencimento, e SHALL guardar valores em centavos inteiros.

#### Scenario: Valor em formato brasileiro
- **WHEN** o Dario informa o valor "1.500,50" e a taxa "2,5"
- **THEN** o empréstimo é salvo com valor 150050 centavos e taxa 2.5% ao mês

#### Scenario: Dados inválidos
- **WHEN** o valor é zero, a taxa é negativa, o número de parcelas é 0 ou o cliente não existe
- **THEN** o empréstimo não é criado e a mensagem de erro aparece junto ao campo

### Requirement: Total pelo método de cálculo
O total a pagar SHALL ser calculado uma única vez e arredondado ao centavo: simples = valor × (1 + taxa × parcelas); composto = valor × (1 + taxa)^parcelas.

#### Scenario: Juros simples
- **WHEN** o empréstimo é de R$ 1.000,00 a 10% ao mês em 3 parcelas, método simples
- **THEN** o total é R$ 1.300,00

#### Scenario: Juros compostos
- **WHEN** o empréstimo é de R$ 1.000,00 a 10% ao mês em 3 parcelas, método composto
- **THEN** o total é R$ 1.331,00

#### Scenario: Taxa zero
- **WHEN** a taxa é 0
- **THEN** o total é igual ao valor emprestado nos dois métodos

### Requirement: Parcelas iguais com a diferença na última
O total SHALL ser dividido em parcelas iguais, em centavos, e a diferença de centavos do arredondamento SHALL ir para a última parcela. A soma das parcelas MUST ser igual ao total.

#### Scenario: Divisão com sobra
- **WHEN** o total é R$ 1.300,00 em 3 parcelas
- **THEN** as parcelas são R$ 433,33, R$ 433,33 e R$ 433,34

### Requirement: Vencimentos mensais sem estouro
A primeira parcela SHALL vencer na data informada e cada parcela seguinte no mesmo dia dos meses seguintes, contado sempre a partir do primeiro vencimento; quando o mês não tiver esse dia, SHALL vencer no último dia do mês.

#### Scenario: Primeiro vencimento no dia 31
- **WHEN** o primeiro vencimento é 31/01/2026 e são 4 parcelas
- **THEN** os vencimentos são 31/01/2026, 28/02/2026, 31/03/2026 e 30/04/2026

#### Scenario: Ano bissexto
- **WHEN** o primeiro vencimento é 29/01/2028 e são 2 parcelas
- **THEN** os vencimentos são 29/01/2028 e 29/02/2028

### Requirement: Listar e consultar empréstimos
A página Empréstimos SHALL listar os empréstimos (cliente, valor, total, parcelas pagas do total) do mais recente para o mais antigo, e a página do empréstimo SHALL mostrar os dados do empréstimo e todas as parcelas com número, vencimento, valor e situação.

#### Scenario: Página do empréstimo
- **WHEN** o Dario abre um empréstimo de 3 parcelas com a primeira paga
- **THEN** a página mostra as 3 parcelas, a primeira como "Paga" com a data do pagamento e as outras com a sua situação
