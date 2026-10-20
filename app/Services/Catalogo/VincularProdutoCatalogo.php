<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoProduto;
use App\Models\Produto;
use Illuminate\Support\Str;

class VincularProdutoCatalogo
{
    public function tentar(
        CatalogoProduto $catalogoProduto
    ): void {
        if ($catalogoProduto->produto_id) {
            return;
        }

        $codigoBarras = trim(
            (string) $catalogoProduto->codigo_barras
        );

        if ($codigoBarras !== '') {
            $produto = Produto::query()
                ->where(
                    'codigo_barras',
                    $codigoBarras
                )
                ->first();

            if ($produto) {
                $catalogoProduto->update([
                    'produto_id' => $produto->id,
                    'vinculo_status' => 'automatico',
                    'vinculo_confianca' => 100,
                    'vinculo_motivo' =>
                        'EAN/código de barras exato',
                ]);

                return;
            }
        }

        $codigoFabricante = $this->normalizar(
            (string) $catalogoProduto
                ->codigo_fabricante
        );

        $marcaFonte = $this->normalizar(
            (string) $catalogoProduto
                ->marca_raw
        );

        if (
            $codigoFabricante === ''
            || $marcaFonte === ''
        ) {
            return;
        }

        $candidatos = Produto::query()
            ->with(
                'marcaRelacionada:id,nome'
            )
            ->where(
                'codigo_fabricante',
                $codigoFabricante
            )
            ->get();

        if ($candidatos->isEmpty()) {
            return;
        }

        $produto = $candidatos->first(
            function (Produto $produto) use (
                $marcaFonte
            ) {
                $marcaJaime = $this->normalizar(
                    (string) $produto
                        ->marcaRelacionada
                        ?->nome
                );

                return $marcaJaime !== ''
                    && $marcaJaime === $marcaFonte;
            }
        );

        if (! $produto) {
            $catalogoProduto->update([
                'vinculo_status' => 'revisao',
                'vinculo_confianca' => null,
                'vinculo_motivo' =>
                    'Referência coincide, mas a marca diverge',
            ]);

            return;
        }

        $catalogoProduto->update([
            'produto_id' => $produto->id,
            'vinculo_status' => 'automatico',
            'vinculo_confianca' => 95,
            'vinculo_motivo' =>
                'Referência do fabricante + marca exatas',
        ]);
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
}
