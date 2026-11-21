<?php

namespace Tests\Feature\Compras;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompraFormularioErrosUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_validacao_preserva_itens_esparsos_e_anexos_e_exibe_erros(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);
        $fornecedor = DB::table('fornecedores')->insertGetId([
            'nome' => 'Fornecedor UX de teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $produto = DB::table('produtos')->insertGetId([
            'nome' => 'Produto UX de teste',
            'descricao' => 'Produto teste',
            'quantidade' => 10,
            'estoque_minimo' => 1,
            'preco_uni' => 19.9,
            'codigo_fabricante' => 'UX-TEST-123',
            'status' => true,
            'fornecedor_id' => null,
            'marca_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dados = [
            'fornecedor_id' => $fornecedor,
            'numero_nf' => '', // aciona a validacao sem gravar compra
            'data_entrada' => now()->toDateString(),
            'valor_produtos' => '19.90',
            'valor_total' => '19.90',
            'itens' => [
                0 => ['produto_id' => $produto, 'descricao' => 'Peça um', 'quantidade' => '1', 'valor_unitario' => '19,90', 'desconto' => '0', 'valor_total' => '19.90'],
                2 => ['produto_id' => $produto, 'descricao' => 'Peça dois', 'quantidade' => '2', 'valor_unitario' => '19,90', 'desconto' => '0', 'valor_total' => '39.80'],
            ],
            'anexos' => [
                0 => ['tipo' => 'nf', 'observacoes' => 'Observação preservada A'],
                2 => ['tipo' => 'boleto', 'observacoes' => 'Observação preservada B'],
            ],
        ];

        $this->actingAs($admin)->from(route('compras.create'))
            ->post(route('compras.store'), $dados)
            ->assertRedirect(route('compras.create'))
            ->assertSessionHasErrors(['numero_nf', 'anexos.0.arquivo', 'anexos.1.arquivo']);

        $this->assertSame('Peça dois', session('_old_input.itens.2.descricao'));
        $this->assertSame('nf', session('_old_input.anexos.0.tipo'));
        $this->assertSame('boleto', session('_old_input.anexos.1.tipo'));

        $pagina = $this->actingAs($admin)->get(route('compras.create'));
        $pagina->assertOk()
            ->assertSee('name="itens[2][descricao]"', false)
            ->assertSee('Peça dois')
            ->assertSee('Observação preservada A')
            ->assertSee('Observação preservada B')
            ->assertSee('class="collapse mt-4 show"', false)
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('id="compra-erros-json"', false);
        $this->assertDatabaseCount('compras', 0);
    }
}
