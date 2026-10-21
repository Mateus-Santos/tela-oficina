<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoProduto;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class VincularProdutoCatalogo
{
    public function avaliar(
        CatalogoProduto $catalogoProduto
    ): array {
        if ($catalogoProduto->produto_id) {
            return $this->decisao(
                status: 'ja_vinculado',
                produtoId: $catalogoProduto->produto_id,
                confianca: $catalogoProduto->vinculo_confianca,
                motivo: $catalogoProduto->vinculo_motivo
                    ?: 'Produto já vinculado'
            );
        }

        $ean =
            $this->normalizarEan(
                $catalogoProduto->codigo_barras
            );

        $marca =
            $this->normalizar(
                (string) $catalogoProduto->marca_raw
            );

        $codigo =
            $this->normalizar(
                (string) $catalogoProduto
                    ->codigo_fabricante
            );

        $candidatosEan =
            $this->candidatosPorEan(
                $ean
            );

        $resultadoMarcaCodigo =
            $this->candidatosPorMarcaCodigo(
                $marca,
                $codigo
            );

        $marcas =
            $resultadoMarcaCodigo[
                'marcas'
            ];

        $candidatosMarcaCodigo =
            $resultadoMarcaCodigo[
                'produtos'
            ];

        if ($candidatosEan->count() > 1) {
            return $this->decisao(
                status: 'revisao',
                motivo:
                    'EAN corresponde a múltiplos produtos operacionais'
            );
        }

        if ($marcas->count() > 1) {
            return $this->decisao(
                status: 'revisao',
                motivo:
                    'Marca do catálogo corresponde a múltiplas marcas operacionais'
            );
        }

        if (
            $candidatosMarcaCodigo
                ->count() > 1
        ) {
            return $this->decisao(
                status: 'revisao',
                motivo:
                    'Marca + referência correspondem a múltiplos produtos operacionais'
            );
        }

        $produtoEan =
            $candidatosEan->first();

        $produtoMarcaCodigo =
            $candidatosMarcaCodigo
                ->first();

        if (
            $produtoEan
            && $produtoMarcaCodigo
            && $produtoEan->id
                !== $produtoMarcaCodigo->id
        ) {
            return $this->decisao(
                status: 'revisao',
                motivo:
                    'Conflito: EAN e marca + referência apontam para produtos diferentes'
            );
        }

        if (
            $produtoEan
            && $produtoMarcaCodigo
        ) {
            return $this->decisao(
                status: 'automatico',
                produtoId: $produtoEan->id,
                confianca: 100,
                motivo:
                    'EAN + marca + referência do fabricante correspondentes'
            );
        }

        if ($produtoEan) {
            if (
                $this->possuiContradicao(
                    $catalogoProduto,
                    $produtoEan
                )
            ) {
                return $this->decisao(
                    status: 'revisao',
                    motivo:
                        'EAN coincide, mas outros dados de identidade divergem'
                );
            }

            return $this->decisao(
                status: 'automatico',
                produtoId: $produtoEan->id,
                confianca: 100,
                motivo:
                    'EAN exato sem conflito nos demais dados disponíveis'
            );
        }

        if ($produtoMarcaCodigo) {
            if (
                $this->possuiContradicao(
                    $catalogoProduto,
                    $produtoMarcaCodigo
                )
            ) {
                return $this->decisao(
                    status: 'revisao',
                    motivo:
                        'Marca + referência coincidem, mas outro dado de identidade diverge'
                );
            }

            return $this->decisao(
                status: 'automatico',
                produtoId:
                    $produtoMarcaCodigo->id,
                confianca: 95,
                motivo:
                    'Marca + referência do fabricante correspondentes'
            );
        }

        return $this->decisao(
            status: 'nao_vinculado',
            motivo:
                $ean === null
                && (
                    $marca === ''
                    || $codigo === ''
                )
                    ? 'Dados de identidade insuficientes para vínculo automático'
                    : 'Nenhum produto operacional compatível encontrado'
        );
    }

    public function tentar(
        CatalogoProduto $catalogoProduto
    ): array {
        $decisao =
            $this->avaliar(
                $catalogoProduto
            );

        if (
            $decisao['status']
            === 'ja_vinculado'
        ) {
            return $decisao;
        }

        $catalogoProduto->update([
            'produto_id' =>
                $decisao['produto_id'],

            'vinculo_status' =>
                $decisao['status'],

            'vinculo_confianca' =>
                $decisao['confianca'],

            'vinculo_motivo' =>
                $decisao['motivo'],
        ]);

        return $decisao;
    }

    private function candidatosPorEan(
        ?string $ean
    ): Collection {
        if ($ean === null) {
            return collect();
        }

        return Produto::query()
            ->with(
                'marcaRelacionada:id,nome'
            )
            ->where(
                'codigo_barras',
                $ean
            )
            ->get();
    }

    private function candidatosPorMarcaCodigo(
        string $marca,
        string $codigo
    ): array {
        if (
            $marca === ''
            || $codigo === ''
        ) {
            return [
                'marcas' => collect(),
                'produtos' => collect(),
            ];
        }

        $marcas =
            Marca::query()
                ->get([
                    'id',
                    'nome',
                ])
                ->filter(
                    fn (Marca $item) =>
                        $this->normalizar(
                            (string) $item->nome
                        ) === $marca
                )
                ->values();

        if ($marcas->count() !== 1) {
            return [
                'marcas' => $marcas,
                'produtos' => collect(),
            ];
        }

        $marcaId =
            $marcas->first()->id;

        $produtos =
            Produto::query()
                ->with(
                    'marcaRelacionada:id,nome'
                )
                ->where(
                    'marca_id',
                    $marcaId
                )
                ->get()
                ->filter(
                    fn (Produto $produto) =>
                        $this->normalizar(
                            (string) $produto
                                ->codigo_fabricante
                        ) === $codigo
                )
                ->values();

        return [
            'marcas' => $marcas,
            'produtos' => $produtos,
        ];
    }

    private function possuiContradicao(
        CatalogoProduto $catalogoProduto,
        Produto $produto
    ): bool {
        $marcaFonte =
            $this->normalizar(
                (string) $catalogoProduto
                    ->marca_raw
            );

        if ($marcaFonte !== '') {
            $marcaProduto =
                $this->normalizar(
                    (string) $produto
                        ->marcaRelacionada
                        ?->nome
                );

            if (
                $marcaProduto === ''
                || $marcaProduto
                    !== $marcaFonte
            ) {
                return true;
            }
        }

        $codigoFonte =
            $this->normalizar(
                (string) $catalogoProduto
                    ->codigo_fabricante
            );

        if ($codigoFonte !== '') {
            $codigoProduto =
                $this->normalizar(
                    (string) $produto
                        ->codigo_fabricante
                );

            if (
                $codigoProduto === ''
                || $codigoProduto
                    !== $codigoFonte
            ) {
                return true;
            }
        }

        $eanFonte =
            $this->normalizarEan(
                $catalogoProduto
                    ->codigo_barras
            );

        $eanProduto =
            $this->normalizarEan(
                $produto->codigo_barras
            );

        if (
            $eanFonte !== null
            && $eanProduto !== null
            && $eanFonte !== $eanProduto
        ) {
            return true;
        }

        return false;
    }

    private function decisao(
        string $status,
        ?int $produtoId = null,
        int|float|string|null $confianca = null,
        ?string $motivo = null
    ): array {
        return [
            'status' => $status,
            'produto_id' => $produtoId,
            'confianca' =>
                $confianca !== null
                    ? (float) $confianca
                    : null,
            'motivo' => $motivo,
        ];
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
        $valor =
            trim(
                (string) $valor
            );

        return $valor !== ''
            ? $valor
            : null;
    }
}
