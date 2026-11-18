<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Nota;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeuxMobileListagensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]));
    }

    private function cliente(): Cliente
    {
        $pessoa = Pessoa::create([
            'nome' => 'Cliente Teste UX com Nome Extenso',
            'telefone_1' => '75999998888',
            'cpf' => '00000000000',
        ]);

        return Cliente::create(['pessoa_id' => $pessoa->id, 'pontos' => 11]);
    }

    public function test_clientes_preserva_tabela_desktop_cards_mobile_e_acoes_reais(): void
    {
        $cliente = $this->cliente();
        $response = $this->get(route('clientes.index'));
        $response->assertOk()
            ->assertSee('feux-listagem-clientes__mobile', false)
            ->assertSee('feux-listagem-clientes__desktop', false)
            ->assertSee('Cliente Teste UX com Nome Extenso')
            ->assertSee('Outras informações')
            ->assertSee('75999998888')
            ->assertSee(route('clientes.show', $cliente->id), false)
            ->assertSee(route('clientes.edit', $cliente->id), false)
            ->assertSee(route('clientes.destroy', $cliente->id), false)
            ->assertSee(route('veiculosclientes.create', ['cliente_id' => $cliente->id]), false);
    }

    public function test_notas_preserva_dados_e_controles_livewire_em_card(): void
    {
        $cliente = $this->cliente();
        $nota = Nota::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'total' => '43.50',
            'subtotal' => '43.50',
            'desconto' => '0.00',
        ]);

        $response = $this->get(route('notas.index'));
        $response->assertOk()
            ->assertSee('feux-nota-card__cabecalho', false)
            ->assertSee('feux-nota-card__dados', false)
            ->assertSee('feux-nota-card__acoes', false)
            ->assertSee('Cliente Teste UX com Nome Extenso')
            ->assertSee('43,50')
            ->assertSee('Data:')
            ->assertSee('PDF Cliente')
            ->assertSee('PDF Interno')
            ->assertSee(route('notas.show', $nota->id), false)
            ->assertSee(route('notas.destroy', $nota->id), false);
    }
}
