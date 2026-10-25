# Spec Delta

## Purpose

Permite ao Dario manter a lista de pessoas a quem empresta dinheiro, com o mínimo para cobrar: nome e telefone.

## ADDED Requirements

### Requirement: Listar clientes
A página Clientes SHALL listar todos os clientes em ordem alfabética, com nome e telefone, e mostrar um estado vazio quando não houver nenhum.

#### Scenario: Lista em ordem alfabética
- **WHEN** existem os clientes "Maria" e "Ana"
- **THEN** a lista mostra "Ana" antes de "Maria", cada um com o seu telefone

#### Scenario: Sem clientes
- **WHEN** não há clientes cadastrados
- **THEN** a página mostra o estado vazio "Nenhum cliente ainda"

### Requirement: Cadastrar cliente
O sistema SHALL cadastrar um cliente com nome (obrigatório, até 255 caracteres) e telefone celular brasileiro com DDD (10 ou 11 dígitos), guardando o telefone só com dígitos.

#### Scenario: Cadastro válido
- **WHEN** o Dario cadastra "Maria da Silva" com o telefone "(85) 99999-0000"
- **THEN** o cliente é criado com telefone "85999990000" e aparece na lista com a mensagem "Cliente cadastrado."

#### Scenario: Dados inválidos
- **WHEN** o nome está vazio ou o telefone tem menos de 10 dígitos
- **THEN** o cliente não é criado e a mensagem de erro aparece junto ao campo
