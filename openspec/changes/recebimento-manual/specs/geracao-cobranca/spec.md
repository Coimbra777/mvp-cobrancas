# Spec Delta

## ADDED Requirements

### Requirement: Rotina só no modo asaas
A rotina diária de geração de QR Codes SHALL criar cobranças apenas no modo asaas; no modo manual SHALL terminar sem chamar o Asaas, informando que o modo manual está ativo.

#### Scenario: Rotina no modo manual
- **WHEN** a rotina roda com o modo manual e há parcelas vencendo em 2 dias
- **THEN** nenhuma chamada é feita ao Asaas, nenhuma cobrança é criada e a saída informa "Modo manual: nada a gerar."
