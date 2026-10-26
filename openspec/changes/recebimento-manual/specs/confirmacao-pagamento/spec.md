# Spec Delta

## ADDED Requirements

### Requirement: Webhook só no modo asaas
No modo manual, `POST /webhooks/asaas` SHALL responder 404 sem gravar nada.

#### Scenario: Webhook no modo manual
- **WHEN** chega um evento com o token correto e o modo é manual
- **THEN** o sistema responde 404 e nenhum evento é gravado
