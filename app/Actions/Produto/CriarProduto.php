<?php

namespace App\Actions\Produto;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CriarProduto
{
    public function execute(array $dados): Produto
    {
        $imagens = [];

        foreach ($dados['imagens'] ?? [] as $imagem) {
            if (!$imagem instanceof UploadedFile) {
                continue;
            }

            $imagens[] = $imagem
                ->store('produtos', 'public');
        }

        try {
            return DB::transaction(
                function () use (
                    $dados,
                    $imagens
                ) {
                    $marca = Marca::query()
                        ->findOrFail($dados['marca_id']);

                    $produto = Produto::create([
                        'nome' => $dados['nome'],
                        'marca_id' => $marca->id,
                        'descricao' => $dados['descricao'],
                        'preco_uni' => $dados['preco_uni'],
                        'quantidade' => $dados['quantidade'] ?? 0,
                        'estoque_minimo' => $dados['estoque_minimo'] ?? 0,
                        'status' => (bool) $dados['status'],
                        'fornecedor_id' => $dados['fornecedor_id'] ?? null,
                        'codigo_fabricante' => $dados['codigo_fabricante'],
                        'codigo_barras' => $dados['codigo_barras'] ?? null,
                        'ncm' => $dados['ncm'] ?? null,
                        'cest' => $dados['cest'] ?? null,
                        'ex_tipi' => $dados['ex_tipi'] ?? null,
                        'origem_mercadoria' => $dados['origem_mercadoria'] ?? null,
                        'unidade_comercial' => $dados['unidade_comercial'] ?? null,
                        'unidade_tributavel' => $dados['unidade_tributavel'] ?? null,
                        'fator_conversao' => $dados['fator_conversao'] ?? null,
                        'peso_liquido' => $dados['peso_liquido'] ?? null,
                        'peso_bruto' => $dados['peso_bruto'] ?? null,
                    ]);

                    foreach ($imagens as $ordem => $caminho) {
                        $produto->imagens()->create([
                            'caminho' => $caminho,
                            'ordem' => $ordem,
                        ]);
                    }

                    $produto->veiculos()->sync(
                        $dados['veiculos']
                    );

                    return $produto->fresh([
                        'marcaRelacionada',
                        'imagens',
                        'veiculos',
                    ]);
                }
            );
        } catch (\Throwable $e) {
            foreach ($imagens as $imagem) {
                Storage::disk('public')
                    ->delete($imagem);
            }

            throw $e;
        }
    }
}
