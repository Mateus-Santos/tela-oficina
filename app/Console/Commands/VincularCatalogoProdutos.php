<?php

namespace App\Console\Commands;

use App\Services\Catalogo\VincularProdutosCatalogo;
use Illuminate\Console\Command;
use Throwable;

class VincularCatalogoProdutos extends Command
{
    protected $signature =
        'catalogo:vincular-produtos
        {--fonte=sky : Código da fonte do catálogo}
        {--dry-run : Avalia sem alterar dados}
        {--limite= : Limita a quantidade processada}';

    protected $description =
        'Tenta vincular produtos do catálogo a produtos operacionais existentes';

    public function handle(
        VincularProdutosCatalogo $service
    ): int {
        $fonte =
            trim(
                (string) $this->option(
                    'fonte'
                )
            );

        $limite =
            $this->option(
                'limite'
            );

        $limite =
            $limite !== null
            && $limite !== ''
                ? max(
                    1,
                    (int) $limite
                )
                : null;

        $dryRun =
            (bool) $this->option(
                'dry-run'
            );

        try {
            $resumo =
                $service->executar(
                    fonteCodigo: $fonte,
                    dryRun: $dryRun,
                    limite: $limite
                );
        } catch (Throwable $e) {
            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }

        $this->table(
            [
                'Métrica',
                'Quantidade',
            ],
            [
                [
                    'Processados',
                    $resumo[
                        'processados'
                    ],
                ],
                [
                    'Já vinculados',
                    $resumo[
                        'ja_vinculados'
                    ],
                ],
                [
                    'Vínculos automáticos',
                    $resumo[
                        'automaticos'
                    ],
                ],
                [
                    'Para revisão',
                    $resumo[
                        'revisao'
                    ],
                ],
                [
                    'Não vinculados',
                    $resumo[
                        'nao_vinculados'
                    ],
                ],
            ]
        );

        if ($dryRun) {
            $this->info(
                'Dry-run concluído. Nenhuma alteração foi gravada.'
            );
        } else {
            $this->info(
                'Processamento de vínculos concluído.'
            );
        }

        return self::SUCCESS;
    }
}
