<?php

namespace App\Services\Produto;

use App\Models\CatalogoProduto;
use App\Models\CatalogoProdutoAplicacao;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use LogicException;

class SincronizarAplicacoesCatalogo
{
    public function __construct(
        private readonly SincronizarAplicacaoCatalogo
            $sincronizarAplicacaoCatalogo,
        private readonly PersistirAplicacoesProduto
            $persistirAplicacoesProduto
    ) {}

    public function diagnosticar(
        ?string $fonteCodigo = null
    ): array {
        $fonteCodigo =
            $this->normalizarFonte(
                $fonteCodigo
            );

        $total =
            $this->consulta(
                $fonteCodigo
            )->count();

        $comProduto =
            $this->consulta(
                $fonteCodigo
            )
                ->whereHas(
                    'catalogoProduto',
                    fn (Builder $query) =>
                        $query->whereNotNull(
                            'produto_id'
                        )
                )
                ->count();

        $resolvidas =
            $this->consulta(
                $fonteCodigo
            )
                ->whereHas(
                    'catalogoProduto',
                    fn (Builder $query) =>
                        $query->whereNotNull(
                            'produto_id'
                        )
                )
                ->whereHas(
                    'catalogoVeiculo',
                    fn (Builder $query) =>
                        $query->whereNotNull(
                            'veiculo_id'
                        )
                )
                ->count();

        $materializadas =
            $this->consulta(
                $fonteCodigo
            )
                ->whereHas(
                    'produtoAplicacao'
                )
                ->count();

        $produtosAfetados =
            CatalogoProduto::query()
                ->whereNotNull(
                    'produto_id'
                )
                ->when(
                    $fonteCodigo,
                    fn (Builder $query) =>
                        $query->whereHas(
                            'fonte',
                            fn (Builder $fonte) =>
                                $fonte->where(
                                    'codigo',
                                    $fonteCodigo
                                )
                        )
                )
                ->whereHas(
                    'aplicacoes'
                )
                ->distinct()
                ->count(
                    'produto_id'
                );

        return [
            'total_catalogo' =>
                $total,

            'com_produto_operacional' =>
                $comProduto,

            'sem_produto_operacional' =>
                $total - $comProduto,

            'resolvidas' =>
                $resolvidas,

            'revisao' =>
                $comProduto - $resolvidas,

            'ja_materializadas' =>
                $materializadas,

            'produtos_afetados' =>
                $produtosAfetados,

            'colisoes' =>
                $this->contarColisoes(
                    $fonteCodigo
                ),
        ];
    }

    public function sincronizar(
        ?string $fonteCodigo = null,
        int $tamanhoLote = 500
    ): array {
        $fonteCodigo =
            $this->normalizarFonte(
                $fonteCodigo
            );

        $tamanhoLote =
            max(
                1,
                $tamanhoLote
            );

        $diagnostico =
            $this->diagnosticar(
                $fonteCodigo
            );

        if (
            $diagnostico[
                'colisoes'
            ] > 0
        ) {
            throw new LogicException(
                'Existem colisões de produto/origem/chave_aplicacao. '
                .'A sincronização foi bloqueada.'
            );
        }

        $produtoIds = [];

        $resultado = [
            'sincronizadas' => 0,
            'resolvidas' => 0,
            'revisao' => 0,
            'produtos_reconstruidos' => 0,
        ];

        $query =
            $this->consulta(
                $fonteCodigo
            )
                ->whereHas(
                    'catalogoProduto',
                    fn (Builder $query) =>
                        $query->whereNotNull(
                            'produto_id'
                        )
                )
                ->with([
                    'catalogoProduto.fonte',
                    'catalogoVeiculo',
                    'produtoAplicacao:id,catalogo_produto_aplicacao_id,produto_id',
                ]);

        $query->chunkById(
            $tamanhoLote,
            function (
                $aplicacoes
            ) use (
                &$produtoIds,
                &$resultado
            ) {
                foreach (
                    $aplicacoes
                    as $catalogoAplicacao
                ) {
                    $produtoAnteriorId =
                        $catalogoAplicacao
                            ->produtoAplicacao
                            ?->produto_id;

                    $produtoAplicacao =
                        $this
                            ->sincronizarAplicacaoCatalogo
                            ->sincronizar(
                                $catalogoAplicacao,
                                false
                            );

                    if (!$produtoAplicacao) {
                        continue;
                    }

                    $resultado[
                        'sincronizadas'
                    ]++;

                    if (
                        $produtoAplicacao
                            ->status_resolucao
                        === 'resolvida'
                    ) {
                        $resultado[
                            'resolvidas'
                        ]++;
                    } else {
                        $resultado[
                            'revisao'
                        ]++;
                    }

                    if ($produtoAnteriorId) {
                        $produtoIds[] =
                            (int) $produtoAnteriorId;
                    }

                    $produtoIds[] =
                        (int) $produtoAplicacao
                            ->produto_id;
                }
            }
        );

        $produtoIds =
            array_values(
                array_unique(
                    array_filter(
                        $produtoIds
                    )
                )
            );

        if ($produtoIds !== []) {
            Produto::query()
                ->whereIn(
                    'id',
                    $produtoIds
                )
                ->chunkById(
                    200,
                    function (
                        $produtos
                    ) use (
                        &$resultado
                    ) {
                        foreach (
                            $produtos
                            as $produto
                        ) {
                            $this
                                ->persistirAplicacoesProduto
                                ->reconstruirVeiculos(
                                    $produto
                                );

                            $resultado[
                                'produtos_reconstruidos'
                            ]++;
                        }
                    }
                );
        }

        return array_merge(
            $diagnostico,
            $resultado,
            [
                'materializadas_apos' =>
                    $this
                        ->consulta(
                            $fonteCodigo
                        )
                        ->whereHas(
                            'produtoAplicacao'
                        )
                        ->count(),
            ]
        );
    }

    private function consulta(
        ?string $fonteCodigo
    ): Builder {
        return CatalogoProdutoAplicacao::query()
            ->when(
                $fonteCodigo,
                fn (Builder $query) =>
                    $query->whereHas(
                        'catalogoProduto.fonte',
                        fn (Builder $fonte) =>
                            $fonte->where(
                                'codigo',
                                $fonteCodigo
                            )
                    )
            );
    }

    private function contarColisoes(
        ?string $fonteCodigo
    ): int {
        $query =
            DB::table(
                'catalogo_produto_aplicacoes as cpa'
            )
                ->join(
                    'catalogo_produtos as cp',
                    'cp.id',
                    '=',
                    'cpa.catalogo_produto_id'
                )
                ->join(
                    'catalogo_fontes as cf',
                    'cf.id',
                    '=',
                    'cp.catalogo_fonte_id'
                )
                ->whereNotNull(
                    'cp.produto_id'
                );

        if ($fonteCodigo) {
            $query->where(
                'cf.codigo',
                $fonteCodigo
            );
        }

        return $query
            ->select([
                'cp.produto_id',
                'cf.codigo',
                'cpa.chave_aplicacao',
            ])
            ->selectRaw(
                'COUNT(*) as total'
            )
            ->groupBy(
                'cp.produto_id',
                'cf.codigo',
                'cpa.chave_aplicacao'
            )
            ->havingRaw(
                'COUNT(*) > 1'
            )
            ->get()
            ->count();
    }

    private function normalizarFonte(
        ?string $fonteCodigo
    ): ?string {
        $fonteCodigo =
            trim(
                (string) $fonteCodigo
            );

        return $fonteCodigo !== ''
            ? $fonteCodigo
            : null;
    }
}
