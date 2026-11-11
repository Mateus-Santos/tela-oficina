# Marketing — proteção e retenção de dados temporários

Este módulo não armazena IP por padrão (`MARKETING_CAPTURA_IP=false`). A cidade e a UF do formulário são fornecidas pelo interessado, não deduzidas por geolocalização de IP. A origem UTM/gclid continua sendo um indicador informado pela URL/sessão, não prova de clique ou conversão validada por plataforma.

Para avaliar a coleta de IP, é necessário revisar a finalidade, os avisos de privacidade, os controles de acesso e a necessidade dessa informação. Quando habilitada por configuração, a aplicação só grava um endereço IP validado e criptografado na coluna `marketing_leads.ip_criptografado` (não incluída nas representações do modelo), sem consultas externas de geolocalização. A chave de aplicação deve ser protegida e mantida fora do repositório.

## Retenção

`php artisan marketing:retencao` informa quantos registros são elegíveis, **sem modificar nada**. A execução explícita `php artisan marketing:retencao --executar` limpa somente IPs cifrados de leads anteriores ao prazo e registros de cadastro pendente com expiração anterior ao prazo. Preserva nome, WhatsApp, itens de orçamento, notas e contas já confirmadas. Faça backup do banco e confira o contexto antes de executar.

Prazos de referência: `MARKETING_RETENCAO_IP_DIAS=30`, `MARKETING_RETENCAO_PENDENTES_DIAS=7` (contados após a expiração do link de cadastro). Não há agendamento automático nesta implementação. Os limites técnicos aceitos são de 1 a 365 dias para IP e de 1 a 30 dias para pendentes. A política operacional de retenção de leads e as solicitações de titulares ainda precisam ser definidas e homologadas separadamente.

O acesso individual do portal só é concedido pela administração e não relaciona automaticamente um lead anônimo a cliente por telefone ou e-mail. A vinculação controlada será tratada em etapa posterior.
