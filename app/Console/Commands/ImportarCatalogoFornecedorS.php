<?php

namespace App\Console\Commands;

use App\Services\Catalogo\FornecedorSCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportarCatalogoFornecedorS extends Command
{
    protected $signature = 'catalogo:importar-fornecedor-s
        {diretorio : Diretório que contém os CSVs exportados pelo coletor do Fornecedor S}
        {--somente-catalogo : Importa apenas catálogo; não cria montadoras/veículos operacionais e não vincula produtos}';

    protected $description = 'Importa a base exportada do coletor do Fornecedor S para a camada de catálogo do Jaime';

    public function handle(FornecedorSCsvImporter $importer): int
    {
        $diretorio = (string) $this->argument('diretorio');

        $this->info('Importando catálogo Fornecedor S...');
        $this->line("Origem: {$diretorio}");

        try {
            $resumo = $importer->importar(
                $diretorio,
                (bool) $this->option('somente-catalogo')
            );
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
        $this->info('Importação concluída sem alterar estoque ou preço de venda da oficina.');
        if ((bool) $this->option('somente-catalogo')) {
            $this->info('Modo somente-catálogo: cadastros operacionais e vínculos não foram alterados.');
        }

        return self::SUCCESS;
    }
}
