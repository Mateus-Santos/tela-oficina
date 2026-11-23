<?php

namespace Tests\Feature\Usabilidade;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class U03U07RotasTest extends TestCase
{
    public function test_rotas_controladas_estao_registradas(): void
    {
        foreach ([
            'ordemservicos.edit', 'ordemservicos.update',
            'ordemservicos.anexos', 'ordemservicos.anexos.store',
            'notas.duplicar', 'notas.duplicar.store', 'notas.anexos.store',
            'compras.duplicar', 'compras.duplicar.store',
            'anexos.update', 'anexos.show', 'anexos.preview', 'anexos.destroy',
        ] as $rota) {
            $this->assertTrue(Route::has($rota), 'Rota ausente: ' . $rota);
        }
    }
}
