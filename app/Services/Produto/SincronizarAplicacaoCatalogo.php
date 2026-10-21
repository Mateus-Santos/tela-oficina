<?php

namespace App\Services\Produto;

use App\Models\CatalogoProdutoAplicacao;
use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use LogicException;

class SincronizarAplicacaoCatalogo
{
    public function __construct(
        private readonly PersistirAplicacoesProduto
            $persistirAplicacoesProduto
    ) {}

    public function sincronizar(
        CatalogoProdutoAplicacao $catalogoAplicacao,
        bool $reconstruirVeiculos = true
    ): ?ProdutoAplicacao {
        $catalogoAplicacao->loadMissing([
            'catalogoProduto.fonte',
            'catalogoVeiculo',
        ]);

        $catalogoProduto =
            $catalogoAplicacao
                ->catalogoProduto;

        if (
            !$catalogoProduto
            || !$catalogoProduto->produto_id
        ) {
            return null;
        }

        $fonte =
            $catalogoProduto->fonte;

        $origem =
            trim(
                (string) (
                    $fonte?->codigo
                    ?? ''
                )
            );

        if ($origem === '') {
            throw new LogicException(
                'Aplicação de catálogo sem fonte válida.'
            );
        }

        $produtoAplicacaoAnterior =
            ProdutoAplicacao::query()
                ->where(
                    'catalogo_produto_aplicacao_id',
                    $catalogoAplicacao->id
                )
                ->first();

        $produtoAnteriorId =
            $produtoAplicacaoAnterior
                ?->produto_id;

        $veiculoId =
            $catalogoAplicacao
                ->catalogoVeiculo
                ?->veiculo_id;

        $status =
            $veiculoId
                ? ProdutoAplicacao::
                    STATUS_RESOLVIDA
                : ProdutoAplicacao::
                    STATUS_REVISAO;

        $produtoAplicacao =
            ProdutoAplicacao::query()
                ->updateOrCreate(
                    [
                        'catalogo_produto_aplicacao_id'
                            => $catalogoAplicacao->id,
                    ],
                    [
                        'produto_id' =>
                            $catalogoProduto
                                ->produto_id,

                        'veiculo_id' =>
                            $veiculoId,

                        'chave_aplicacao' =>
                            $catalogoAplicacao
                                ->chave_aplicacao,

                        'mes_ano' =>
                            $catalogoAplicacao
                                ->mes_ano,

                        'modelo' =>
                            $catalogoAplicacao
                                ->modelo,

                        'motor' =>
                            $catalogoAplicacao
                                ->motor,

                        'comp' =>
                            $catalogoAplicacao
                                ->comp,

                        'valvulas' =>
                            $catalogoAplicacao
                                ->valvulas,

                        'cilindros' =>
                            $catalogoAplicacao
                                ->cilindros,

                        'combustivel' =>
                            $catalogoAplicacao
                                ->combustivel,

                        'complemento' =>
                            $catalogoAplicacao
                                ->complemento,

                        'origem' =>
                            $origem,

                        'status_resolucao' =>
                            $status,
                    ]
                );

        if ($reconstruirVeiculos) {
            $produtoIds =
                collect([
                    $produtoAnteriorId,
                    $produtoAplicacao
                        ->produto_id,
                ])
                    ->filter()
                    ->unique()
                    ->values();

            foreach (
                $produtoIds
                as $produtoId
            ) {
                $produto =
                    Produto::query()
                        ->find(
                            $produtoId
                        );

                if (!$produto) {
                    continue;
                }

                $this
                    ->persistirAplicacoesProduto
                    ->reconstruirVeiculos(
                        $produto
                    );
            }
        }

        return $produtoAplicacao;
    }
}
