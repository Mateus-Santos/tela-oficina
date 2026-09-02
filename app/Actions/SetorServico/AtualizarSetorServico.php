<?php

namespace App\Actions\SetorServico;

use App\Models\SetorServico;

class AtualizarSetorServico
{
    public function execute(SetorServico $setorServico, array $dados): SetorServico
    {
        $setorServico->update([
            'setor' => $dados['setor'],
            'nivel' => $dados['nivel'],
        ]);

        return $setorServico->refresh();
    }
}
