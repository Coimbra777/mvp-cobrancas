# Spec Delta

## MODIFIED Requirements

### Requirement: Conteúdo da página
A página SHALL mostrar o primeiro nome do cliente, "Parcela N de M", o valor, o vencimento, a situação (em aberto, em atraso há N dias ou paga), o QR Code, o Pix copia e cola com o botão "Copiar código Pix" e, no modo asaas, até quando o código vale, sem outros dados pessoais. No modo manual, o Pix vem da chave do Dario e a página SHALL oferecer o envio do comprovante. Parcela paga SHALL mostrar "Paga" sem QR Code.

#### Scenario: Parcela em aberto
- **WHEN** a página de uma parcela que vence em 2 dias é aberta
- **THEN** ela mostra o valor, o vencimento, "Em aberto", o QR Code e o botão "Copiar código Pix"

#### Scenario: Parcela paga
- **WHEN** a página de uma parcela quitada é aberta
- **THEN** ela mostra "Paga", sem QR Code

#### Scenario: Modo manual
- **WHEN** o modo é manual e a página de uma parcela em aberto é aberta
- **THEN** ela mostra o Pix copia e cola gerado com a chave do Dario, o QR Code desse código e o campo "Enviar comprovante"

### Requirement: Antes da janela e em falha
No modo asaas, para parcela que vence em mais de 5 dias, a página SHALL informar a partir de quando o Pix fica disponível; no modo manual o Pix aparece a qualquer momento. Se o Pix não puder ser gerado, a página SHALL abrir com o aviso "Não conseguimos gerar o Pix agora. Tente de novo em alguns minutos."

#### Scenario: Vencimento distante
- **WHEN** o modo é asaas e a página de uma parcela que vence em 20/11/2026 é aberta em 25/10/2026
- **THEN** ela mostra "O Pix desta parcela fica disponível a partir de 15/11/2026" sem chamar o Asaas

#### Scenario: Vencimento distante no modo manual
- **WHEN** o modo é manual e a página de uma parcela que vence daqui a 2 meses é aberta
- **THEN** ela já mostra o Pix copia e cola e o QR Code
