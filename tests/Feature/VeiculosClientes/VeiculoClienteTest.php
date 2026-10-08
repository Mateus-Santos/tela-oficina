<?php

namespace Tests\Feature\VeiculosClientes;

use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;
use App\Models\VeiculosCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VeiculoClienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $veiculoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'colaborador' => true,
            'permitions' => 1,
            'status' => 1,
        ]);

        $agora = now();

        $montadoraId = DB::table('montadoras')
            ->insertGetId([
                'nome' => 'Montadora Teste',
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);

        $this->veiculoId = DB::table('veiculos')
            ->insertGetId([
                'nome' => 'Modelo Teste',
                'montadora_id' => $montadoraId,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
    }

    private function criarCliente(
        string $nome
    ): Cliente {
        $pessoa = Pessoa::create([
            'nome' => $nome,
        ]);

        return Cliente::create([
            'pessoa_id' => $pessoa->id,
            'pontos' => 0,
        ]);
    }

    private function dadosVeiculo(
        array $sobrescrever = []
    ): array {
        return array_merge(
            [
                'placa' => 'ABC1D23',
                'ano' => 2024,
                'cor' => 'Branco',
                'veiculo_id' => $this->veiculoId,
            ],
            $sobrescrever
        );
    }

    public function test_administrador_cria_veiculo_com_um_cliente(): void
    {
        $cliente = $this->criarCliente(
            'Cliente Principal'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo([
                    'clientes' => [
                        $cliente->id,
                    ],
                ])
            );

        $response->assertRedirect(
            route('veiculosclientes.index')
        );

        $response->assertSessionHasNoErrors();

        $veiculoCliente = VeiculosCliente::where(
            'placa',
            'ABC1D23'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $cliente->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $this->assertSame(
            $cliente->id,
            (int) $veiculoCliente->cliente_id
        );
    }

    public function test_administrador_cria_veiculo_com_multiplos_clientes(): void
    {
        $clienteA = $this->criarCliente(
            'Cliente A'
        );

        $clienteB = $this->criarCliente(
            'Cliente B'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo([
                    'clientes' => [
                        $clienteA->id,
                        $clienteB->id,
                    ],
                ])
            );

        $response->assertSessionHasNoErrors();

        $veiculoCliente = VeiculosCliente::where(
            'placa',
            'ABC1D23'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteA->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteB->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $this->assertCount(
            2,
            $veiculoCliente
                ->clientes()
                ->get()
        );
    }

    public function test_administrador_nao_cria_veiculo_sem_cliente(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->from(
                route('veiculosclientes.create')
            )
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo()
            );

        $response
            ->assertRedirect(
                route('veiculosclientes.create')
            )
            ->assertSessionHasErrors('clientes');

        $this->assertDatabaseMissing(
            'veiculos_clientes',
            [
                'placa' => 'ABC1D23',
            ]
        );
    }

    public function test_edicao_adiciona_novo_cliente(): void
    {
        $clienteA = $this->criarCliente(
            'Cliente A'
        );

        $clienteB = $this->criarCliente(
            'Cliente B'
        );

        $veiculoCliente = VeiculosCliente::create(
            $this->dadosVeiculo([
                'cliente_id' => $clienteA->id,
            ])
        );

        $veiculoCliente
            ->clientes()
            ->sync([
                $clienteA->id,
            ]);

        $response = $this
            ->actingAs($this->admin)
            ->patch(
                route(
                    'veiculosclientes.update',
                    $veiculoCliente->id
                ),
                $this->dadosVeiculo([
                    'clientes' => [
                        $clienteA->id,
                        $clienteB->id,
                    ],
                ])
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteA->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteB->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );
    }

    public function test_edicao_remove_cliente_do_vinculo(): void
    {
        $clienteA = $this->criarCliente(
            'Cliente A'
        );

        $clienteB = $this->criarCliente(
            'Cliente B'
        );

        $veiculoCliente = VeiculosCliente::create(
            $this->dadosVeiculo([
                'cliente_id' => $clienteA->id,
            ])
        );

        $veiculoCliente
            ->clientes()
            ->sync([
                $clienteA->id,
                $clienteB->id,
            ]);

        $response = $this
            ->actingAs($this->admin)
            ->patch(
                route(
                    'veiculosclientes.update',
                    $veiculoCliente->id
                ),
                $this->dadosVeiculo([
                    'clientes' => [
                        $clienteB->id,
                    ],
                ])
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteA->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $this->assertDatabaseHas(
            'cliente_veiculo_cliente',
            [
                'cliente_id' => $clienteB->id,
                'veiculo_cliente_id' => $veiculoCliente->id,
            ]
        );

        $veiculoCliente->refresh();

        $this->assertSame(
            $clienteB->id,
            (int) $veiculoCliente->cliente_id
        );
    }

    public function test_clientes_duplicados_sao_rejeitados(): void
    {
        $cliente = $this->criarCliente(
            'Cliente Duplicado'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo([
                    'clientes' => [
                        $cliente->id,
                        $cliente->id,
                    ],
                ])
            );

        $response->assertSessionHasErrors(
            'clientes.1'
        );

        $this->assertDatabaseMissing(
            'veiculos_clientes',
            [
                'placa' => 'ABC1D23',
            ]
        );
    }

    public function test_placa_duplicada_e_rejeitada(): void
    {
        $cliente = $this->criarCliente(
            'Cliente'
        );

        $veiculoCliente = VeiculosCliente::create(
            $this->dadosVeiculo([
                'cliente_id' => $cliente->id,
            ])
        );

        $veiculoCliente
            ->clientes()
            ->sync([
                $cliente->id,
            ]);

        $outroCliente = $this->criarCliente(
            'Outro Cliente'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo([
                    'clientes' => [
                        $outroCliente->id,
                    ],
                ])
            );

        $response->assertSessionHasErrors(
            'placa'
        );

        $this->assertSame(
            1,
            VeiculosCliente::where(
                'placa',
                'ABC1D23'
            )->count()
        );
    }

    public function test_placa_e_normalizada_antes_de_salvar(): void
    {
        $cliente = $this->criarCliente(
            'Cliente'
        );

        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('veiculosclientes.store'),
                $this->dadosVeiculo([
                    'placa' => 'abc-1d23',
                    'clientes' => [
                        $cliente->id,
                    ],
                ])
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'veiculos_clientes',
            [
                'placa' => 'ABC1D23',
            ]
        );
    }
}
