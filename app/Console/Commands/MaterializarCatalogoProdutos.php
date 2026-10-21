<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MaterializarCatalogoProdutos extends Command
{
    protected $signature =
        'catalogo:materializar-produtos
        {--fonte=sky : Mantido apenas por compatibilidade}
        {--dry-run : Mantido apenas por compatibilidade}
        {--limite= : Mantido apenas por compatibilidade}';

    protected $description =
        'OBSOLETO: materialização automática de produtos foi descontinuada';

    public function handle(): int
    {
        $this->error(
            'Este comando foi descontinuado.'
        );

        $this->warn(
            'O catálogo não pode criar produtos operacionais.'
        );

        $this->info(
            'Use catalogo:vincular-produtos para tentar '
            .'vincular produtos já existentes.'
        );

        return self::FAILURE;
    }
}
