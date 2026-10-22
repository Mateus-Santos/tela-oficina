<?php

namespace App\Services\Produto;

use App\Models\Produto;

class ResolverDadosCatalogoProduto
{
    public function resolver(
        Produto $produto
    ): array {
        $catalogos = $produto->catalogos;

        if ($catalogos->isEmpty()) {
            return $this->vazio(
                $produto
            );
        }

        $catalogo = $catalogos
            ->sortByDesc(
                fn ($item) =>
                    $item->ultima_coleta_em
                    ?? $item->updated_at
                    ?? $item->created_at
            )
            ->first();

        if (! $catalogo) {
            return $this->vazio(
                $produto
            );
        }

        $estado = $catalogo->estado;

        $imagemCatalogo = $catalogo
            ->imagens
            ->sortByDesc(
                fn ($imagem) =>
                    (int) $imagem->principal
            )
            ->first();

        $imagemUrl =
            $imagemCatalogo?->url_thumb
            ?: $imagemCatalogo?->url_original
            ?: $catalogo->imagem_thumb_url
            ?: $catalogo->imagem_url;

        $precoObservado =
            $estado?->preco_venda_observado !== null
                ? (float) $estado
                    ->preco_venda_observado
                : null;

        $fonte =
            $catalogo->fonte?->nome
            ?: 'Catálogo externo';

        return [
            'possui_catalogo' => true,

            'catalogo_produto_id' =>
                $catalogo->id,

            'fonte' =>
                $fonte,

            'codigo_externo' =>
                $catalogo->chave_externa,

            'imagem_url' =>
                $imagemUrl,

            'preco_observado' =>
                $precoObservado,

            'preco_venda' =>
                $precoObservado
                ?? (float) $produto->preco_uni,

            'origem_preco' =>
                $precoObservado !== null
                    ? $fonte
                    : 'Cadastro Jaime',

            'estoque_observado' =>
                $estado?->estoque_observado !== null
                    ? (int) $estado
                        ->estoque_observado
                    : null,

            'promocao' =>
                (bool) (
                    $estado?->promocao_detectada
                    ?? false
                ),

            'promocao_texto' =>
                $estado?->promocao_texto,

            'ultima_coleta_em' =>
                $estado?->ultima_coleta_em
                ?? $catalogo->ultima_coleta_em,

            'total_aplicacoes' =>
                $catalogo
                    ->aplicacoes
                    ->where(
                        'ativa',
                        true
                    )
                    ->count(),

            'total_atributos' =>
                $catalogo
                    ->atributos
                    ->count(),

            'total_imagens_catalogo' =>
                $catalogo
                    ->imagens
                    ->count(),
        ];
    }

    private function vazio(
        Produto $produto
    ): array {
        return [
            'possui_catalogo' => false,
            'catalogo_produto_id' => null,
            'fonte' => null,
            'codigo_externo' => null,
            'imagem_url' => null,
            'preco_observado' => null,

            'preco_venda' =>
                (float) $produto->preco_uni,

            'origem_preco' =>
                'Cadastro Jaime',

            'estoque_observado' => null,
            'promocao' => false,
            'promocao_texto' => null,
            'ultima_coleta_em' => null,
            'total_aplicacoes' => 0,
            'total_atributos' => 0,
            'total_imagens_catalogo' => 0,
        ];
    }
}
