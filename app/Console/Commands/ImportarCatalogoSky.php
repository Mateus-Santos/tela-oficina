<?php

namespace App\Console\Commands;

use App\Services\Catalogo\SkyCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportarCatalogoSky extends Command
{
    protected $signature = 'catalogo:importar-sky
        {diretorio : Diretório que contém os CSVs exportados pelo coletor Sky}';

    protected $description = 'Importa a base exportada do coletor Sky para a camada de catálogo do Jaime';

    public function handle(SkyCsvImporter $importer): int
    {
        $diretorio = (string) $this->argument('diretorio');

        $this->info('Importando catálogo Sky...');
        $this->line("Origem: {$diretorio}");

        try {
            $resumo = $importer->importar($diretorio);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Item', 'Quantidade'],
            collect($resumo)
                ->map(fn ($valor, $chave) => [$chave, $valor])
                ->values()
                ->all()
        );

        $this->newLine();
        $this->info('Importação concluída sem alterar estoque, preço de venda ou aplicações manuais do Jaime.');

        return self::SUCCESS;
    }
}
