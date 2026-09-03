<?php

namespace App\Actions\Compra;

use App\Actions\Anexo\CriarAnexo;
use App\Models\Compra;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CriarCompra
{
    public function __construct(
        private CriarAnexo $criarAnexo
    ) {
    }

    public function execute(array $dados, array $anexos = []): Compra
    {
        return DB::transaction(function () use ($dados, $anexos) {
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

            $compra = Compra::create([
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
                'status' => 'pendente',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);

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

            foreach ($anexos as $anexo) {
                if (!isset($anexo['arquivo']) || !$anexo['arquivo'] instanceof UploadedFile) {
                    continue;
                }

                $this->criarAnexo->execute(
                    $compra,
                    $anexo['arquivo'],
                    $anexo['tipo'],
                    $anexo['observacoes'] ?? null
                );
            }

            return $compra->load(['itens', 'anexos']);
        });
    }
}
