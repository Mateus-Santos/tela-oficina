<?php

namespace Tests\Feature\Financeiro;

use Tests\TestCase;

class BuscaComprasVinculadasUxTest extends TestCase
{
    public function test_formulario_conserva_vinculo_original_e_oferece_busca_acessivel(): void
    {
        $view = file_get_contents(resource_path('views/contas_pagar/_form.blade.php'));
        $this->assertIsString($view);
        $this->assertStringContainsString('name="compras[]"', $view);
        $this->assertStringContainsString('data-compra-checkbox', $view);
        $this->assertStringContainsString('id="compras-vinculadas-busca"', $view);
        $this->assertStringContainsString('id="compras-vinculadas-filtro"', $view);
        $this->assertStringContainsString('id="compras-vinculadas-resultado"', $view);
        $this->assertStringContainsString('atualizarVisibilidade', $view);
        $this->assertStringContainsString('data-fornecedor-id=', $view);
    }
}
