<?php

namespace App\Actions\ContasPagar;

use App\Models\ContaPagar;
use Illuminate\Support\Facades\DB;

class CriarContaPagar
{
    public function execute(array $dados): ContaPagar
    {
        return DB::transaction(function () use ($dados) {
            return ContaPagar::create([
                'fornecedor_id' => $dados['fornecedor_id'] ?? null,
                'nota_id' => $dados['nota_id'] ?? null,
                'descricao' => $dados['descricao'],
                'valor' => $dados['valor'],
                'data_emissao' => $dados['data_emissao'],
                'data_vencimento' => $dados['data_vencimento'],
                'status' => 'aberta',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);
        });
    }
}
