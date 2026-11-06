<?php

return [
    // Dados publicos. Atualize pelo .env (sem usar env() diretamente nas views).
    'whatsapp' => env('OFICINA_WHATSAPP', '5575981086649'),
    'telefone' => env('OFICINA_TELEFONE', '5575981086649'),
    'telefone_exibicao' => env('OFICINA_TELEFONE_EXIBICAO', '(75) 98108-6649'),
    'telefone_2' => env('OFICINA_TELEFONE_2', ''),
    'email' => env('OFICINA_EMAIL', ''),
    'endereco' => env('OFICINA_ENDERECO', 'Rua Vasco da Gama, 160 - Cruzeiro, Feira de Santana - BA'),
];
