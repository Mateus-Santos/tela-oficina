<?php

namespace App\Actions\Produto;

use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AtualizarProduto
{
    public function execute(Produto $produto, array $dados): Produto
    {
        $novaImagem = null;
        $imagemAnterior = $produto->img;

        if (
            isset($dados['img'])
            && $dados['img'] instanceof UploadedFile
        ) {
            $novaImagem = $dados['img']->store('produtos', 'public');
        }

        try {
            $produto = DB::transaction(function () use ($produto, $dados, $novaImagem) {
                $marca = Marca::query()->findOrFail($dados['marca_id']);

                $produto->update([
                    'nome' => $dados['nome'],
                    'marca' => $marca->nome,
                    'marca_id' => $marca->id,
                    'descricao' => $dados['descricao'],
                    'preco_uni' => $dados['preco_uni'],
                    'quantidade' => $dados['quantidade'],
                    'codigo_fabricante' => $dados['codigo_fabricante'],
                    'codigo_barras' => $dados['codigo_barras'] ?? null,
                    'img' => $novaImagem ?? $produto->img,
                ]);

                $produto->veiculos()->sync($dados['veiculos']);

                return $produto->fresh([
                    'marcaRelacionada',
                    'veiculos',
                ]);
            });
        } catch (\Throwable $e) {
            if ($novaImagem) {
                Storage::disk('public')->delete($novaImagem);
            }

            throw $e;
        }

        if (
            $novaImagem
            && $imagemAnterior
            && Storage::disk('public')->exists($imagemAnterior)
        ) {
            Storage::disk('public')->delete($imagemAnterior);
        }

        return $produto;
    }
}
