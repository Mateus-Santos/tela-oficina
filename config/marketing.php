<?php

return [
    // Abertura gradual: desativado por padrao, inclusive em producao.
    // Requer infraestrutura de e-mail verificada antes de habilitar.
    'cadastro_cliente_habilitado' => (bool) env('MARKETING_CADASTRO_CLIENTE', false),
    // Conversao so apos homologacao operacional e da visibilidade ao cliente.
    'conversao_nota_habilitada' => (bool) env('MARKETING_CONVERSAO_NOTA', false),
    // Acompanhamento de solicitacoes no portal: opt-in independente da conversao.
    'portal_solicitacoes_habilitado' => (bool) env('MARKETING_PORTAL_SOLICITACOES', false),
    // Pagina inicial opcional do portal, sem modificar os modulos ja utilizados.
    'portal_inicio_habilitado' => (bool) env('MARKETING_PORTAL_INICIO', false),
    // IP nao e necessario para receber um orcamento; opt-in tecnico e legal.
    'captura_ip_habilitada' => (bool) env('MARKETING_CAPTURA_IP', false),
    // Retencao usada pelo comando manual marketing:retencao.
    'retencao_ip_dias' => (int) env('MARKETING_RETENCAO_IP_DIAS', 30),
    'retencao_pendentes_dias' => (int) env('MARKETING_RETENCAO_PENDENTES_DIAS', 7),
    // Os modulos novos exigem tambem concessao individual a cada cliente.
    // Sem registro de acesso (ou sem tabela), o acesso e negado.
];
