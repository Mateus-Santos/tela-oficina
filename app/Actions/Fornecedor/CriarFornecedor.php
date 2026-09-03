<?php

namespace App\Actions\Fornecedor;

use App\Models\Fornecedor;

class CriarFornecedor
{
    public function execute(array $dados): Fornecedor
    {
        return Fornecedor::create([
            'nome' => $dados['nome'],
            'cnpj' => $dados['cnpj'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
        ]);
    }
}
