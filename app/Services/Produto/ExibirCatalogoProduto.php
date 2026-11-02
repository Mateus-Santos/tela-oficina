<?php

namespace App\Services\Produto;

use App\Models\CatalogoProduto;
use App\Support\Catalogo\PrecoVendaForge;

final class ExibirCatalogoProduto
{
    public function dados(CatalogoProduto $catalogo): array
    {
        $edicao = $catalogo->edicao;
        $sugerido = PrecoVendaForge::arredondar(
            $catalogo->estado?->preco_venda_sugerido
                ?? $catalogo->estado?->preco_venda_observado
        );
        $preco = $edicao?->preco_venda !== null
            ? (string) $edicao->preco_venda : $sugerido;
        $imagem = $catalogo->imagens->sortByDesc('principal')->first();

        return [
            'nome' => $edicao?->nome ?: $catalogo->descricao ?: $catalogo->codigo_fabricante ?: $catalogo->chave_externa,
            'descricao' => $edicao?->descricao ?: $catalogo->descricao,
            'marca' => $edicao?->marca ?: $catalogo->marca_raw,
            'referencia' => $edicao?->codigo_fabricante ?: $catalogo->codigo_fabricante,
            'codigo_barras' => $edicao?->codigo_barras ?: $catalogo->codigo_barras,
            'preco' => $preco,
            'sugerido' => $sugerido,
            'imagem' => $imagem?->url_thumb ?: $imagem?->url_original
                ?: $catalogo->imagem_thumb_url ?: $catalogo->imagem_url,
        ];
    }
}
