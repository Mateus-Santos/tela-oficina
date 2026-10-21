<?php

namespace App\Services\Catalogo;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use RuntimeException;

class VincularProdutosCatalogo
{
    public function __construct(
        private readonly VincularProdutoCatalogo
            $vinculador
    ) {}

    public function executar(
        string $fonteCodigo = 'sky',
        bool $dryRun = true,
        ?int $limite = null
    ): array {
        $fonte =
            CatalogoFonte::query()
                ->where(
                    'codigo',
                    $fonteCodigo
                )
                ->first();

        if (!$fonte) {
            throw new RuntimeException(
                "Fonte de catálogo '{$fonteCodigo}' não encontrada."
            );
        }

        $query =
            CatalogoProduto::query()
                ->where(
                    'catalogo_fonte_id',
                    $fonte->id
                )
                ->orderBy('id');

        if (
            $limite !== null
            && $limite > 0
        ) {
            $query->limit(
                $limite
            );
        }

        $resumo = [
            'processados' => 0,
            'ja_vinculados' => 0,
            'automaticos' => 0,
            'revisao' => 0,
            'nao_vinculados' => 0,
        ];

        foreach (
            $query->get()
            as $catalogoProduto
        ) {
            $resumo[
                'processados'
            ]++;

            $decisao =
                $dryRun
                    ? $this
                        ->vinculador
                        ->avaliar(
                            $catalogoProduto
                        )
                    : $this
                        ->vinculador
                        ->tentar(
                            $catalogoProduto
                        );

            match (
                $decisao['status']
            ) {
                'ja_vinculado' =>
                    $resumo[
                        'ja_vinculados'
                    ]++,

                'automatico' =>
                    $resumo[
                        'automaticos'
                    ]++,

                'revisao' =>
                    $resumo[
                        'revisao'
                    ]++,

                default =>
                    $resumo[
                        'nao_vinculados'
                    ]++,
            };
        }

        return $resumo;
    }
}
