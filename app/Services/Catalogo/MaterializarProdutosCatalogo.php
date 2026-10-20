<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MaterializarProdutosCatalogo
{
    public function executar(
        string $fonteCodigo = 'sky',
        bool $dryRun = true,
        ?int $limite = null
    ): array {
        $fonte = CatalogoFonte::query()
            ->where(
                'codigo',
                $fonteCodigo
            )
            ->first();

        if (! $fonte) {
            throw new RuntimeException(
                "Fonte de catálogo '{$fonteCodigo}' não encontrada."
            );
        }

        $query = CatalogoProduto::query()
            ->where(
                'catalogo_fonte_id',
                $fonte->id
            )
            ->orderBy('id');

        if (
            $limite !== null
            && $limite > 0
        ) {
            $query->limit($limite);
        }

        $catalogoProdutos = $query->get();

        if ($dryRun) {
            return $this->simular(
                $catalogoProdutos
            );
        }

        return DB::transaction(
            fn () => $this->materializar(
                $catalogoProdutos
            )
        );
    }

    private function simular(
        $catalogoProdutos
    ): array {
        $estado = $this->montarEstadoAtual();

        $resumo = $this->novoResumo(
            $catalogoProdutos->count()
        );

        foreach ($catalogoProdutos as $catalogoProduto) {
            if ($catalogoProduto->produto_id) {
                $resumo['ja_vinculados']++;

                continue;
            }

            $marca = $this->normalizar(
                (string) $catalogoProduto->marca_raw
            );

            $codigo = $this->normalizar(
                (string) $catalogoProduto
                    ->codigo_fabricante
            );

            $ean = $this->normalizarEan(
                $catalogoProduto->codigo_barras
            );

            if (
                $marca === ''
                || $codigo === ''
            ) {
                $resumo['ignorados_dados_insuficientes']++;

                continue;
            }

            if (
                ! isset(
                    $estado['marcas'][$marca]
                )
            ) {
                $estado['marcas'][$marca] = [
                    'nova' => true,
                ];

                $resumo['marcas_criar']++;
            }

            $chaveProduto = $this->chaveProduto(
                $marca,
                $codigo
            );

            if (
                isset(
                    $estado['produtos_codigo'][$chaveProduto]
                )
            ) {
                if (
                    $estado['produtos_codigo']
                        [$chaveProduto]['novo']
                ) {
                    $resumo[
                        'catalogos_consolidados_mesmo_produto'
                    ]++;
                } else {
                    $resumo[
                        'vinculos_por_marca_codigo'
                    ]++;
                }

                continue;
            }

            if (
                $ean !== null
                && isset(
                    $estado['produtos_ean'][$ean]
                )
            ) {
                $produtoEan =
                    $estado['produtos_ean'][$ean];

                if (
                    $produtoEan['marca']
                    !== $marca
                ) {
                    $resumo['conflitos_ean']++;

                    continue;
                }

                $resumo['vinculos_por_ean']++;

                $estado['produtos_codigo']
                    [$chaveProduto] =
                    $produtoEan;

                continue;
            }

            $novoProduto = [
                'id' => null,
                'marca' => $marca,
                'codigo' => $codigo,
                'ean' => $ean,
                'novo' => true,
            ];

            $estado['produtos_codigo']
                [$chaveProduto] =
                $novoProduto;

            if ($ean !== null) {
                $estado['produtos_ean'][$ean] =
                    $novoProduto;
            }

            $resumo['produtos_criar']++;
        }

        return $resumo;
    }

    private function materializar(
        $catalogoProdutos
    ): array {
        $estado = $this->montarEstadoAtual();

        $resumo = $this->novoResumo(
            $catalogoProdutos->count()
        );

        foreach ($catalogoProdutos as $catalogoProduto) {
            if ($catalogoProduto->produto_id) {
                $resumo['ja_vinculados']++;

                continue;
            }

            $marcaNormalizada = $this->normalizar(
                (string) $catalogoProduto->marca_raw
            );

            $codigoNormalizado = $this->normalizar(
                (string) $catalogoProduto
                    ->codigo_fabricante
            );

            $ean = $this->normalizarEan(
                $catalogoProduto->codigo_barras
            );

            if (
                $marcaNormalizada === ''
                || $codigoNormalizado === ''
            ) {
                $resumo[
                    'ignorados_dados_insuficientes'
                ]++;

                continue;
            }

            $marca = $estado['marcas']
                [$marcaNormalizada]['model']
                ?? null;

            if (! $marca) {
                $marca = Marca::query()->create([
                    'nome' => $marcaNormalizada,
                    'ativo' => true,
                ]);

                $estado['marcas']
                    [$marcaNormalizada] = [
                        'model' => $marca,
                        'nova' => true,
                    ];

                $resumo['marcas_criar']++;
            }

            $chaveProduto = $this->chaveProduto(
                $marcaNormalizada,
                $codigoNormalizado
            );

            if (
                isset(
                    $estado['produtos_codigo'][$chaveProduto]
                )
            ) {
                $produtoInfo =
                    $estado['produtos_codigo']
                        [$chaveProduto];

                $this->vincular(
                    $catalogoProduto,
                    $produtoInfo['id'],
                    95,
                    'Referência do fabricante + marca exatas'
                );

                if ($produtoInfo['novo']) {
                    $resumo[
                        'catalogos_consolidados_mesmo_produto'
                    ]++;
                } else {
                    $resumo[
                        'vinculos_por_marca_codigo'
                    ]++;
                }

                continue;
            }

            if (
                $ean !== null
                && isset(
                    $estado['produtos_ean'][$ean]
                )
            ) {
                $produtoInfo =
                    $estado['produtos_ean'][$ean];

                if (
                    $produtoInfo['marca']
                    !== $marcaNormalizada
                ) {
                    $catalogoProduto->update([
                        'vinculo_status' => 'revisao',
                        'vinculo_confianca' => null,
                        'vinculo_motivo' =>
                            'EAN já utilizado por produto de outra marca',
                    ]);

                    $resumo['conflitos_ean']++;

                    continue;
                }

                $this->vincular(
                    $catalogoProduto,
                    $produtoInfo['id'],
                    100,
                    'EAN + marca correspondentes'
                );

                $estado['produtos_codigo']
                    [$chaveProduto] =
                    $produtoInfo;

                $resumo['vinculos_por_ean']++;

                continue;
            }

            $produto = Produto::query()->create([
                'nome' => $this->nomeProduto(
                    $catalogoProduto
                ),

                'descricao' => trim(
                    (string) $catalogoProduto
                        ->descricao
                ),

                'quantidade' => 0,
                'estoque_minimo' => 0,
                'preco_uni' => 0,

                'codigo_fabricante' =>
                    $codigoNormalizado,

                'codigo_barras' => $ean,

                'status' => true,

                'fornecedor_id' => null,
                'marca_id' => $marca->id,

                'ncm' => $this->normalizarNcm(
                    $catalogoProduto->ncm
                ),

                'cest' => null,
                'ex_tipi' => null,

                'origem_mercadoria' =>
                    $this->normalizarOrigem(
                        $catalogoProduto
                            ->origem_raw
                    ),

                'unidade_comercial' =>
                    $this->normalizarUnidade(
                        $catalogoProduto
                            ->unidade
                    ),

                'unidade_tributavel' =>
                    $this->normalizarUnidade(
                        $catalogoProduto
                            ->unidade
                    ),

                'fator_conversao' => null,
                'peso_liquido' => null,
                'peso_bruto' => null,
            ]);

            $this->vincular(
                $catalogoProduto,
                $produto->id,
                100,
                'Produto criado automaticamente a partir do catálogo'
            );

            $produtoInfo = [
                'id' => $produto->id,
                'marca' => $marcaNormalizada,
                'codigo' => $codigoNormalizado,
                'ean' => $ean,
                'novo' => true,
            ];

            $estado['produtos_codigo']
                [$chaveProduto] =
                $produtoInfo;

            if ($ean !== null) {
                $estado['produtos_ean'][$ean] =
                    $produtoInfo;
            }

            $resumo['produtos_criar']++;
        }

        return $resumo;
    }

    private function montarEstadoAtual(): array
    {
        $marcas = [];

        foreach (
            Marca::query()->get()
            as $marca
        ) {
            $normalizada = $this->normalizar(
                $marca->nome
            );

            $marcas[$normalizada] = [
                'model' => $marca,
                'nova' => false,
            ];
        }

        $produtosCodigo = [];
        $produtosEan = [];

        $produtos = Produto::query()
            ->with(
                'marcaRelacionada:id,nome'
            )
            ->get();

        foreach ($produtos as $produto) {
            $marca = $this->normalizar(
                (string) $produto
                    ->marcaRelacionada
                    ?->nome
            );

            $codigo = $this->normalizar(
                (string) $produto
                    ->codigo_fabricante
            );

            if (
                $marca === ''
                || $codigo === ''
            ) {
                continue;
            }

            $ean = $this->normalizarEan(
                $produto->codigo_barras
            );

            $info = [
                'id' => $produto->id,
                'marca' => $marca,
                'codigo' => $codigo,
                'ean' => $ean,
                'novo' => false,
            ];

            $produtosCodigo[
                $this->chaveProduto(
                    $marca,
                    $codigo
                )
            ] = $info;

            if ($ean !== null) {
                $produtosEan[$ean] = $info;
            }
        }

        return [
            'marcas' => $marcas,
            'produtos_codigo' =>
                $produtosCodigo,
            'produtos_ean' =>
                $produtosEan,
        ];
    }

    private function vincular(
        CatalogoProduto $catalogoProduto,
        int $produtoId,
        int $confianca,
        string $motivo
    ): void {
        $catalogoProduto->update([
            'produto_id' => $produtoId,
            'vinculo_status' => 'automatico',
            'vinculo_confianca' => $confianca,
            'vinculo_motivo' => $motivo,
        ]);
    }

    private function novoResumo(
        int $processados
    ): array {
        return [
            'catalogo_processados' =>
                $processados,

            'ja_vinculados' => 0,

            'marcas_criar' => 0,

            'produtos_criar' => 0,

            'vinculos_por_marca_codigo' => 0,

            'vinculos_por_ean' => 0,

            'catalogos_consolidados_mesmo_produto' => 0,

            'conflitos_ean' => 0,

            'ignorados_dados_insuficientes' => 0,
        ];
    }

    private function chaveProduto(
        string $marca,
        string $codigo
    ): string {
        return $marca.'|'.$codigo;
    }

    private function nomeProduto(
        CatalogoProduto $catalogoProduto
    ): string {
        $nome = trim(
            (string) $catalogoProduto->descricao
        );

        if ($nome === '') {
            $nome = trim(
                (string) $catalogoProduto
                    ->codigo_fabricante
            );
        }

        $nome = mb_strtoupper($nome);

        return mb_substr(
            $nome,
            0,
            150
        );
    }

    private function normalizar(
        string $valor
    ): string {
        return Str::of($valor)
            ->ascii()
            ->upper()
            ->squish()
            ->value();
    }

    private function normalizarEan(
        mixed $valor
    ): ?string {
        $ean = trim(
            (string) $valor
        );

        return $ean !== ''
            ? $ean
            : null;
    }

    private function normalizarNcm(
        mixed $valor
    ): ?string {
        $ncm = preg_replace(
            '/\D+/',
            '',
            (string) $valor
        );

        if (
            ! is_string($ncm)
            || strlen($ncm) !== 8
        ) {
            return null;
        }

        return $ncm;
    }

    private function normalizarUnidade(
        mixed $valor
    ): ?string {
        $unidade = $this->normalizar(
            (string) $valor
        );

        if (
            $unidade === ''
            || mb_strlen($unidade) > 6
        ) {
            return null;
        }

        return $unidade;
    }

    private function normalizarOrigem(
        mixed $valor
    ): ?int {
        $origem = trim(
            (string) $valor
        );

        if (
            ! preg_match(
                '/^[0-8]$/',
                $origem
            )
        ) {
            return null;
        }

        return (int) $origem;
    }
}
