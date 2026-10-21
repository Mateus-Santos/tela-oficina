<?php

namespace App\Services\Produto;

use App\Models\Produto;
use App\Models\ProdutoAplicacao;
use Illuminate\Support\Facades\DB;

class PersistirAplicacoesProduto
{
    public function __construct(
        private readonly GerarChaveAplicacao
            $gerarChaveAplicacao
    ) {}

    public function sincronizarManuais(
        Produto $produto,
        array $aplicacoes
    ): void {
        DB::transaction(
            function () use (
                $produto,
                $aplicacoes
            ) {
                $chavesMantidas = [];

                foreach (
                    $aplicacoes
                    as $aplicacao
                ) {
                    $veiculoId =
                        isset(
                            $aplicacao[
                                'veiculo_id'
                            ]
                        )
                            ? (int) $aplicacao[
                                'veiculo_id'
                            ]
                            : null;

                    if (
                        !$veiculoId
                    ) {
                        continue;
                    }

                    $chave =
                        $this
                            ->gerarChaveAplicacao
                            ->gerar(
                                $veiculoId,
                                $aplicacao
                            );

                    $chavesMantidas[] =
                        $chave;

                    $produto
                        ->aplicacoes()
                        ->updateOrCreate(
                            [
                                'origem' =>
                                    ProdutoAplicacao::
                                        ORIGEM_MANUAL,

                                'chave_aplicacao' =>
                                    $chave,
                            ],
                            [
                                'veiculo_id' =>
                                    $veiculoId,

                                'catalogo_produto_aplicacao_id'
                                    => null,

                                'mes_ano' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'mes_ano'
                                        ]
                                        ?? null
                                    ),

                                'modelo' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'modelo'
                                        ]
                                        ?? null
                                    ),

                                'motor' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'motor'
                                        ]
                                        ?? null
                                    ),

                                'comp' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'comp'
                                        ]
                                        ?? null
                                    ),

                                'valvulas' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'valvulas'
                                        ]
                                        ?? null
                                    ),

                                'cilindros' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'cilindros'
                                        ]
                                        ?? null
                                    ),

                                'combustivel' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'combustivel'
                                        ]
                                        ?? null
                                    ),

                                'complemento' =>
                                    $this->nulo(
                                        $aplicacao[
                                            'complemento'
                                        ]
                                        ?? null
                                    ),

                                'status_resolucao' =>
                                    ProdutoAplicacao::
                                        STATUS_RESOLVIDA,
                            ]
                        );
                }

                $manuais =
                    $produto
                        ->aplicacoes()
                        ->where(
                            'origem',
                            ProdutoAplicacao::
                                ORIGEM_MANUAL
                        );

                if (
                    $chavesMantidas === []
                ) {
                    $manuais->delete();
                } else {
                    $manuais
                        ->whereNotIn(
                            'chave_aplicacao',
                            $chavesMantidas
                        )
                        ->delete();
                }

                $this->reconstruirVeiculos(
                    $produto
                );
            }
        );
    }

    public function reconstruirVeiculos(
        Produto $produto
    ): void {
        $veiculos =
            $produto
                ->aplicacoes()
                ->reorder()
                ->whereNotNull(
                    'veiculo_id'
                )
                ->select(
                    'veiculo_id'
                )
                ->distinct()
                ->pluck(
                    'veiculo_id'
                )
                ->map(
                    fn ($id) => (int) $id
                )
                ->all();

        $produto
            ->veiculos()
            ->sync($veiculos);
    }

    private function nulo(
        mixed $valor
    ): ?string {
        if ($valor === null) {
            return null;
        }

        $valor =
            trim(
                (string) $valor
            );

        return $valor !== ''
            ? $valor
            : null;
    }
}
