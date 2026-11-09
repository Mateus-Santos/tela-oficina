<?php

namespace Tests\Feature\PortalCliente;

use App\Models\CatalogoFonte;
use App\Models\CatalogoProduto;
use App\Models\Marca;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutosDisponiveisTest extends TestCase
{
    use RefreshDatabase;

    private int $sequencia = 0;

    private function usuario(
        string $perfil = User::PERFIL_CLIENTE,
        bool $ativo = true
    ): User {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => $ativo,
        ]);
    }

    private function produto(
        string $nome,
        string $quantidade = '1.000',
        bool $ativo = true,
        ?Marca $marca = null
    ): Produto {
        $this->sequencia++;

        $marca ??= Marca::firstOrCreate(
            ['nome' => 'MARCA PADRAO PORTAL'],
            ['ativo' => true]
        );

        return Produto::create([
            'nome' => $nome,
            'descricao' => 'OBSERVACAO_INTERNA_RESERVADA',
            'codigo_fabricante' => 'REF-PRIVADA-'.$this->sequencia,
            'quantidade' => $quantidade,
            'estoque_minimo' => '1.000',
            'preco_uni' => '49.90',
            'status' => $ativo,
            'marca_id' => $marca->id,
        ]);
    }

    public function test_visitante_nao_acessa_catalogo_do_cliente(): void
    {
        $this->get(route('cliente.produtos.index'))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_admin_e_colaborador_nao_acessam_portal_do_cliente(): void
    {
        foreach ([
            User::PERFIL_ADMIN,
            User::PERFIL_COLABORADOR,
        ] as $perfil) {
            $this->actingAs($this->usuario($perfil))
                ->get(route('cliente.produtos.index'))
                ->assertForbidden();
        }
    }

    public function test_cliente_ve_somente_produtos_ativos_com_estoque(): void
    {
        $this->produto('PIVO VISIVEL PORTAL', '2.000', true);
        $this->produto('FILTRO ZERADO PORTAL', '0.000', true);
        $this->produto('CORREIA INATIVA PORTAL', '5.000', false);
        $this->produto('BUCHA NEGATIVA PORTAL', '-1.000', true);

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index'))
            ->assertOk()
            ->assertSee('PIVO VISIVEL PORTAL')
            ->assertDontSee('FILTRO ZERADO PORTAL')
            ->assertDontSee('CORREIA INATIVA PORTAL')
            ->assertDontSee('BUCHA NEGATIVA PORTAL');
    }

    public function test_cliente_nao_recebe_informacoes_administrativas(): void
    {
        $produto = $this->produto('FILTRO PUBLICO PORTAL', '7.000');

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index'))
            ->assertOk()
            ->assertSee('FILTRO PUBLICO PORTAL')
            ->assertSee('R$ 49,90')
            ->assertDontSee($produto->codigo)
            ->assertDontSee($produto->codigo_fabricante)
            ->assertDontSee('OBSERVACAO_INTERNA_RESERVADA')
            ->assertDontSee('Estoque mínimo')
            ->assertDontSee('Movimentações de Estoque')
            ->assertDontSee('Financeiro dos Sócios');

        $this->get(route('produtos.index'))
            ->assertForbidden();

        $this->get(route('produtos.show', $produto))
            ->assertForbidden();
    }

    public function test_catalogo_externo_nao_vira_produto_publico(): void
    {
        $fonte = CatalogoFonte::create([
            'codigo' => 'fornecedor_s',
            'nome' => 'Fornecedor S',
            'tipo' => 'fornecedor',
            'ativo' => true,
        ]);

        CatalogoProduto::create([
            'catalogo_fonte_id' => $fonte->id,
            'chave_externa' => 'EXTERNO-001',
            'descricao' => 'REFERENCIA EXTERNA RESERVADA',
        ]);

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index'))
            ->assertOk()
            ->assertDontSee('REFERENCIA EXTERNA RESERVADA')
            ->assertSee('Nenhum produto disponível encontrado');

        $this->assertSame(0, Produto::count());
    }

    public function test_busca_filtra_por_nome_ou_marca(): void
    {
        $marca = Marca::create([
            'nome' => 'MARCA ORION',
            'ativo' => true,
        ]);

        $this->produto('FILTRO PREMIUM PORTAL');
        $this->produto('VELA ESPECIAL PORTAL', '1.000', true, $marca);

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index', [
                'busca' => 'ORION',
            ]))
            ->assertOk()
            ->assertSee('VELA ESPECIAL PORTAL')
            ->assertDontSee('FILTRO PREMIUM PORTAL');

        $this->get(route('cliente.produtos.index', [
            'busca' => 'FILTRO',
        ]))
            ->assertOk()
            ->assertSee('FILTRO PREMIUM PORTAL')
            ->assertDontSee('VELA ESPECIAL PORTAL');
    }

    public function test_busca_nao_pesquisa_campos_internos(): void
    {
        $this->produto('PRODUTO VISIVEL PORTAL');

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index', [
                'busca' => 'REF-PRIVADA',
            ]))
            ->assertOk()
            ->assertDontSee('PRODUTO VISIVEL PORTAL')
            ->assertSee('Nenhum produto disponível encontrado');
    }

    public function test_paginacao_limita_doze_produtos_por_pagina(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $this->produto(sprintf('ITEM PORTAL %02d', $i));
        }

        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index'))
            ->assertOk()
            ->assertSee('ITEM PORTAL 01')
            ->assertDontSee('ITEM PORTAL 13');

        $this->get(route('cliente.produtos.index', [
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('ITEM PORTAL 13')
            ->assertDontSee('ITEM PORTAL 01');
    }

    public function test_cliente_bloqueado_nao_acessa_produtos(): void
    {
        $this->actingAs($this->usuario(
            User::PERFIL_CLIENTE,
            false
        ))
            ->get(route('cliente.produtos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_busca_longa_e_rejeitada(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('cliente.produtos.index', [
                'busca' => str_repeat('A', 81),
            ]))
            ->assertSessionHasErrors('busca');
    }
}
