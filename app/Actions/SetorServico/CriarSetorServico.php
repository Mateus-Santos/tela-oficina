<?php

namespace App\Actions\SetorServico;

use App\Models\SetorServico;

class CriarSetorServico
{
    public function execute(array $dados): SetorServico
    {
        return SetorServico::create([
            'setor' => $dados['setor'],
            'nivel' => $dados['nivel'],
        ]);
    }
}
