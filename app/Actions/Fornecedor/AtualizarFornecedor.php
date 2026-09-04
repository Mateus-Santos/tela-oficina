<?php

namespace App\Actions\Fornecedor;

use App\Models\Fornecedor;

class AtualizarFornecedor
{
    public function execute(Fornecedor $fornecedor, array $dados): Fornecedor
    {
        $fornecedor->update([
            'nome' => $dados['nome'],
            'cnpj' => $dados['cnpj'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
        ]);

        return $fornecedor->refresh();
    }
}
