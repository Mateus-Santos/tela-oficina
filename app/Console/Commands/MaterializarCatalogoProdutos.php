<?php

namespace App\Console\Commands;

use App\Services\Catalogo\MaterializarProdutosCatalogo;
use Illuminate\Console\Command;
use Throwable;

class MaterializarCatalogoProdutos extends Command
{
    protected $signature = 'catalogo:materializar-produtos
        {--fonte=sky : Código da fonte do catálogo}
        {--dry-run : Simula a materialização sem alterar dados}
        {--limite= : Limita a quantidade de registros processados}';

    protected $description =
        'Materializa produtos do catálogo externo no cadastro operacional do Jaime';

    public function handle(
        MaterializarProdutosCatalogo $service
    ): int {
        $fonte = (string) $this->option(
            'fonte'
        );

        $dryRun = (bool) $this->option(
            'dry-run'
        );

        $limite = $this->option(
            'limite'
        );

        $limite = $limite !== null
            && $limite !== ''
            ? (int) $limite
            : null;

        if ($dryRun) {
            $this->warn(
                'MODO DRY-RUN: nenhuma alteração será gravada.'
            );
        } else {
            $this->warn(
                'MODO EXECUÇÃO: produtos e vínculos poderão ser criados.'
            );
        }

        try {
            $resumo = $service->executar(
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

        $this->newLine();

        $this->table(
            [
                'Métrica',
                'Quantidade',
            ],
            collect($resumo)
                ->map(
                    fn ($valor, $chave) => [
                        $chave,
                        $valor,
                    ]
                )
                ->values()
                ->all()
        );

        $this->newLine();

        if ($dryRun) {
            $this->info(
                'Simulação concluída. Nenhum registro foi alterado.'
            );
        } else {
            $this->info(
                'Materialização concluída.'
            );
        }

        return self::SUCCESS;
    }
}
