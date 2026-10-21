<?php

namespace App\Services\Catalogo;

use RuntimeException;

class MaterializarProdutosCatalogo
{
    public function executar(
        string $fonteCodigo = 'sky',
        bool $dryRun = true,
        ?int $limite = null
    ): array {
        throw new RuntimeException(
            'Materialização automática de produtos foi descontinuada. '
            .'O catálogo não pode criar produtos operacionais. '
            .'Use catalogo:vincular-produtos para tentar vincular '
            .'produtos já existentes.'
        );
    }
}
