# Spec Delta

## Purpose

Página pública que o cliente recebe pelo WhatsApp para pagar uma parcela por Pix, sem login e sem expor dados pessoais.

## ADDED Requirements

### Requirement: Acesso por token aleatório
Cada parcela SHALL ter um token aleatório de pelo menos 40 caracteres, e `/p/{token}` SHALL responder 404 para token desconhecido ou para o id numérico da parcela.

#### Scenario: Token desconhecido
- **WHEN** alguém abre `/p/token-inexistente`
- **THEN** o sistema responde 404

### Requirement: Conteúdo da página
A página SHALL mostrar o primeiro nome do cliente, "Parcela N de M", o valor, o vencimento, a situação (em aberto, em atraso há N dias ou paga), o QR Code, o Pix copia e cola com o botão "Copiar código Pix" e até quando o código vale, sem outros dados pessoais. Parcela paga SHALL mostrar "Paga" sem QR Code.

#### Scenario: Parcela em aberto
- **WHEN** a página de uma parcela que vence em 2 dias é aberta
- **THEN** ela mostra o valor, o vencimento, "Em aberto", o QR Code e o botão "Copiar código Pix"

#### Scenario: Parcela paga
- **WHEN** a página de uma parcela quitada é aberta
- **THEN** ela mostra "Paga", sem QR Code

### Requirement: Antes da janela e em falha
Para parcela que vence em mais de 5 dias, a página SHALL informar a partir de quando o Pix fica disponível; se o Pix não puder ser gerado, SHALL abrir com o aviso "Não conseguimos gerar o Pix agora. Tente de novo em alguns minutos."

#### Scenario: Vencimento distante
- **WHEN** a página de uma parcela que vence em 20/11/2026 é aberta em 25/10/2026
- **THEN** ela mostra "O Pix desta parcela fica disponível a partir de 15/11/2026" sem chamar o Asaas

### Requirement: Fora de buscadores e de cache
A página SHALL enviar `X-Robots-Tag: noindex, nofollow` e `Cache-Control: no-store` e limitar o acesso a 30 requisições por minuto por endereço.

#### Scenario: Cabeçalhos
- **WHEN** a página é aberta
- **THEN** a resposta traz `X-Robots-Tag: noindex, nofollow` e `Cache-Control: no-store`
