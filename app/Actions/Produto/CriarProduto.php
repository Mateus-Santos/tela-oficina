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
        $imagem = null;

        if (
            isset($dados['img'])
            && $dados['img'] instanceof UploadedFile
        ) {
            $imagem = $dados['img']->store('produtos', 'public');
        }

        try {
            return DB::transaction(function () use ($dados, $imagem) {
                $marca = Marca::query()->findOrFail($dados['marca_id']);

                $produto = Produto::create([
                    'nome' => $dados['nome'],
                    'marca_id' => $marca->id,
                    'descricao' => $dados['descricao'],
                    'preco_uni' => $dados['preco_uni'],
                    'quantidade' => $dados['quantidade'] ?? 0,
                    'estoque_minimo' => $dados['estoque_minimo'] ?? 0,
                    'status' => $dados['status'] ?? true,
                    'fornecedor_id' => $dados['fornecedor_id'] ?? null,
                    'codigo_fabricante' => $dados['codigo_fabricante'],
                    'codigo_barras' => $dados['codigo_barras'] ?? null,
                    'img' => $imagem,
                ]);

                $produto->veiculos()->sync($dados['veiculos']);

                return $produto;
            });
        } catch (\Throwable $e) {
            if ($imagem) {
                Storage::disk('public')->delete($imagem);
            }

            throw $e;
        }
    }
}
