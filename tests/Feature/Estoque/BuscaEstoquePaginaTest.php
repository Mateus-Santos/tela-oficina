<?php

namespace Tests\Feature\Estoque;

use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuscaEstoquePaginaTest extends TestCase
{
    use RefreshDatabase;

    private Produto $principal;
    private Produto $outro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => 1,
        ]));

        $marca = Marca::query()->create(['nome' => 'FORNECEDOR S', 'ativo' => true]);
        $outraMarca = Marca::query()->create(['nome' => 'MARCA DIFERENTE', 'ativo' => true]);

        $this->principal = Produto::query()->create([
            'nome' => 'PIVO DE SUSPENSAO TESTE',
            'descricao' => 'Pivo inferior',
            'codigo' => 'PRD-00000263',
            'codigo_fabricante' => 'PVI2183',
            'codigo_barras' => '7891234567890',
            'marca_id' => $marca->id,
            'quantidade' => '1.000',
            'estoque_minimo' => '0.000',
            'preco_uni' => '29.90',
            'status' => true,
        ]);

        $this->outro = Produto::query()->create([
            'nome' => 'FILTRO DE OLEO TESTE',
            'descricao' => 'Outro produto',
            'codigo' => 'PRD-00000999',
            'codigo_fabricante' => 'WO130',
            'codigo_barras' => '7899999999999',
            'marca_id' => $outraMarca->id,
            'quantidade' => '0.000',
            'estoque_minimo' => '0.000',
            'preco_uni' => '10.00',
            'status' => true,
        ]);
    }

    public function test_estoque_lista_produtos_sem_filtros(): void
    {
        $this->get(route('estoque.index'))
            ->assertOk()
            ->assertSee($this->principal->nome)
            ->assertSee($this->outro->nome);
    }

    public function test_busca_rapida_encontra_marca_nome_e_codigos_na_rota_real(): void
    {
        foreach (['FORNECEDOR S', 'PIVO', 'PVI2183', 'PVI-2183', '7891234567890', 'PRD-00000263', 'FORNECEDOR S PIVO'] as $termo) {
            $this->get(route('estoque.index', ['nome' => $termo]))
                ->assertOk()
                ->assertSee($this->principal->nome)
                ->assertDontSee($this->outro->nome);
        }
    }

    public function test_filtros_avancados_encontram_marca_referencia_e_parte_do_codigo_de_barras(): void
    {
        foreach ([
            ['marca' => 'FORNECEDOR S'],
            ['codigo_fabricante' => 'PVI'],
            ['codigo' => '00000263'],
            ['codigo_barras' => '345678'],
        ] as $filtro) {
            $this->get(route('estoque.index', $filtro))
                ->assertOk()
                ->assertSee($this->principal->nome)
                ->assertDontSee($this->outro->nome);
        }
    }

    public function test_filtro_situacao_e_formularios_independentes(): void
    {
        $this->get(route('estoque.index', ['situacao' => 'zerado']))
            ->assertOk()
            ->assertSee($this->outro->nome)
            ->assertDontSee($this->principal->nome);

        $html = $this->get(route('estoque.index'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'class="filtros-container__form"'));
        $this->assertStringContainsString('Aplicar filtros avançados', $html);
    }
}
