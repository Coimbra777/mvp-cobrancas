# Spec Delta

## ADDED Requirements

### Requirement: Selo de comprovante pendente
Na página Cobranças, a parcela com comprovante pendente SHALL mostrar o selo "Comprovante enviado" com um link para a tela Comprovantes.

#### Scenario: Comprovante aguardando
- **WHEN** a parcela 2 de 3 de Maria tem um comprovante pendente
- **THEN** a linha dessa parcela em Cobranças mostra "Comprovante enviado" com link para Comprovantes
