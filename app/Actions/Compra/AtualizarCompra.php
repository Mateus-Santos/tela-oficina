<?php

namespace App\Actions\Compra;

use App\Models\Compra;
use Illuminate\Support\Facades\DB;

class AtualizarCompra
{
    public function execute(Compra $compra, array $dados): Compra
    {
        return DB::transaction(function () use ($compra, $dados) {

            if ($compra->status === 'aprovada') {
                throw new \DomainException(
                    'Uma compra aprovada não pode ser alterada.'
                );
            }

            if ($compra->status === 'cancelada') {
                throw new \DomainException(
                    'Uma compra cancelada não pode ser alterada.'
                );
            }

            $desconto = (float) ($dados['desconto'] ?? 0);
            $frete = (float) ($dados['frete'] ?? 0);
            $outrasDespesas = (float) ($dados['outras_despesas'] ?? 0);

            $valorProdutos = 0;

            foreach ($dados['itens'] as $item) {
                $quantidade = (float) $item['quantidade'];
                $valorUnitario = (float) $item['valor_unitario'];
                $descontoItem = (float) ($item['desconto'] ?? 0);

                $valorTotalItem = ($quantidade * $valorUnitario) - $descontoItem;

                if ($valorTotalItem < 0) {
                    throw new \InvalidArgumentException(
                        'O valor total de um item não pode ser negativo.'
                    );
                }

                $valorProdutos += $valorTotalItem;
            }

            $valorTotal = $valorProdutos
                - $desconto
                + $frete
                + $outrasDespesas;

            if ($valorTotal < 0) {
                throw new \InvalidArgumentException(
                    'O valor total da compra não pode ser negativo.'
                );
            }

            $compra->update([
                'fornecedor_id' => $dados['fornecedor_id'],
                'numero_nf' => $dados['numero_nf'],
                'serie_nf' => $dados['serie_nf'] ?? null,
                'chave_nf' => $dados['chave_nf'] ?? null,
                'data_emissao' => $dados['data_emissao'] ?? null,
                'data_entrada' => $dados['data_entrada'],
                'valor_produtos' => $valorProdutos,
                'desconto' => $desconto,
                'frete' => $frete,
                'outras_despesas' => $outrasDespesas,
                'valor_total' => $valorTotal,
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

            /*
             * Como a compra ainda não foi aprovada,
             * seus itens podem ser substituídos.
             */
            $compra->itens()->delete();

            foreach ($dados['itens'] as $item) {
                $quantidade = (float) $item['quantidade'];
                $valorUnitario = (float) $item['valor_unitario'];
                $descontoItem = (float) ($item['desconto'] ?? 0);

                $valorTotalItem = ($quantidade * $valorUnitario) - $descontoItem;

                $compra->itens()->create([
                    'produto_id' => $item['produto_id'],
                    'descricao' => $item['descricao'],
                    'quantidade' => $quantidade,
                    'quantidade_conferida' => $item['quantidade_conferida'] ?? null,
                    'valor_unitario' => $valorUnitario,
                    'desconto' => $descontoItem,
                    'valor_total' => $valorTotalItem,
                ]);
            }

            return $compra->load('itens');
        });
    }
}
