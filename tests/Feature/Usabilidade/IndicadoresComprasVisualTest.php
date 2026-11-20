<?php

namespace Tests\Feature\Usabilidade;

use App\Models\Compra;
use Tests\TestCase;

class IndicadoresComprasVisualTest extends TestCase
{
    private function renderizar(string $estoque, string $financeiro): string
    {
        $compra = new Compra();
        $compra->setAttribute('integracao_estoque_status', $estoque);
        $compra->setAttribute('integracao_financeiro_status', $financeiro);

        return view('compra.partials.indicadores-integracao', [
            'compra' => $compra,
        ])->render();
    }

    public function test_exibe_todos_os_sete_estados_do_estoque(): void
    {
        $estados = [
            'sem_itens' => 'Sem itens',
            'pendente' => 'Sem entrada',
            'parcial' => 'Entrada parcial',
            'lancado' => 'Entrada identificada',
            'estornado' => 'Estorno identificado',
            'verificar_estorno' => 'Conferir estorno',
            'sem_entrada' => 'Sem entrada (cancelada)',
        ];

        foreach ($estados as $estado => $rotulo) {
            $html = $this->renderizar($estado, 'nao_vinculada');
            $this->assertStringContainsString('Estoque: '.$rotulo, $html);
            $this->assertStringContainsString('Financeiro: Sem vínculo', $html);
        }
    }

    public function test_exibe_todos_os_tres_estados_financeiros(): void
    {
        $estados = [
            'nao_vinculada' => 'Sem vínculo',
            'vinculada' => 'Conta vinculada',
            'vinculo_cancelado' => 'Vínculo cancelado',
        ];

        foreach ($estados as $estado => $rotulo) {
            $html = $this->renderizar('pendente', $estado);
            $this->assertStringContainsString('Financeiro: '.$rotulo, $html);
            $this->assertStringNotContainsString('Financeiro: Paga', $html);
            $this->assertStringNotContainsString('Financeiro: Quitada', $html);
        }
    }

    public function test_alerta_de_estorno_exige_conferencia(): void
    {
        $html = $this->renderizar('verificar_estorno', 'vinculada');
        $this->assertStringContainsString('Estoque: Conferir estorno', $html);
        $this->assertStringContainsString('bg-warning text-dark', $html);
        $this->assertStringNotContainsString('Estorno concluído', $html);
        $this->assertStringContainsString('Vínculo não significa pagamento', $html);
    }

    public function test_nao_confunde_entrada_identificada_com_conciliacao(): void
    {
        $html = $this->renderizar('lancado', 'vinculada');
        $this->assertStringContainsString('Entrada identificada', $html);
        $this->assertStringContainsString('Não comprova conciliação de quantidades', $html);
        $this->assertStringNotContainsString('Estoque conciliado', $html);
    }

    public function test_estados_desconhecidos_usam_fallback_sem_sucesso_falso(): void
    {
        $html = $this->renderizar('inexistente', 'inexistente');
        $this->assertStringContainsString('Estoque: Não informado', $html);
        $this->assertStringContainsString('Financeiro: Não informado', $html);
        $this->assertStringNotContainsString('bg-success', $html);
    }

    public function test_listagem_referencia_parcial_de_indicadores(): void
    {
        $blade = file_get_contents(resource_path('views/compra/index.blade.php'));
        $this->assertIsString($blade);
        $this->assertStringContainsString("@include('compra.partials.indicadores-integracao'", $blade);
        $this->assertStringContainsString('<th>Integrações</th>', $blade);
    }
}
