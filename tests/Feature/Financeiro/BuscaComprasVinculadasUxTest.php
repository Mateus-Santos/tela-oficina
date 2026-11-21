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

    public function test_compras_tem_busca_por_identificadores_e_lista_com_scroll_proprio(): void
    {
        $view = file_get_contents(resource_path('views/contas_pagar/_form.blade.php'));
        $this->assertIsString($view);
        $this->assertStringContainsString('id="compras-vinculadas-busca"', $view);
        $this->assertStringContainsString('id="compras-vinculadas-lista"', $view);
        $this->assertStringContainsString('data-compra-busca=', $view);
        $this->assertStringContainsString('item.dataset.compraBusca', $view);
        $this->assertStringContainsString('overflow-y: auto', $view);
        $this->assertStringContainsString('max-height: min(55vh, 24rem)', $view);
        $this->assertStringContainsString('tabindex="0"', $view);
        $this->assertStringContainsString('aria-describedby="compras-vinculadas-resultado"', $view);
        $this->assertTrue(strpos($view, 'id="compras-vinculadas-busca"') < strpos($view, 'id="compras-vinculadas-lista"'));
        $this->assertTrue(strpos($view, 'id="compras-vinculadas-lista"') < strpos($view, 'id="compras-vinculadas-total"'));
        $this->assertStringContainsString('name="compras[]"', $view);
        $this->assertStringContainsString('data-fornecedor-id=', $view);
    }
}
