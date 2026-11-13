<?php

return [
    // Dados publicos. Atualize pelo .env (sem usar env() diretamente nas views).
    'whatsapp' => env('OFICINA_WHATSAPP', '5575981086649'),
    'telefone' => env('OFICINA_TELEFONE', '5575981086649'),
    'telefone_exibicao' => env('OFICINA_TELEFONE_EXIBICAO', '(75) 98108-6649'),
    'telefone_2' => env('OFICINA_TELEFONE_2', ''),
    'email' => env('OFICINA_EMAIL', ''),
    // Identidade legal e canal de privacidade: preencher somente com dados verificados.
    'razao_social' => env('OFICINA_RAZAO_SOCIAL', ''),
    'cnpj' => env('OFICINA_CNPJ', ''),
    'privacidade_email' => env('OFICINA_PRIVACIDADE_EMAIL', ''),
    'endereco' => env('OFICINA_ENDERECO', 'Rua Vasco da Gama, 160 - Cruzeiro, Feira de Santana - BA'),
];
