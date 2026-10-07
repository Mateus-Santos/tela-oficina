<?php

namespace Tests\Feature\Clientes;

use App\Models\Cliente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'colaborador' => true,
            'permitions' => 1,
            'status' => 1,
        ]);
    }

    private function criarCliente(
        array $dadosPessoa = [],
        array $dadosCliente = []
    ): Cliente {
        $pessoa = Pessoa::create(
            array_merge(
                [
                    'nome' => 'Cliente Teste',
                    'cpf' => null,
                    'rg' => null,
                    'data_nascimento' => null,
                    'telefone_1' => null,
                    'telefone_2' => null,
                ],
                $dadosPessoa
            )
        );

        return Cliente::create(
            array_merge(
                [
                    'pessoa_id' => $pessoa->id,
                    'pontos' => 0,
                ],
                $dadosCliente
            )
        );
    }

    public function test_cria_cliente_apenas_com_nome(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Maria da Silva',
                ]
            );

        $response->assertRedirect(
            route('clientes.index')
        );

        $this->assertDatabaseHas(
            'pessoas',
            [
                'nome' => 'Maria da Silva',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );

        $pessoa = Pessoa::where(
            'nome',
            'Maria da Silva'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'clientes',
            [
                'pessoa_id' => $pessoa->id,
                'pontos' => 0,
            ]
        );
    }

    public function test_cria_cliente_com_campos_opcionais_vazios(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Cliente Sem Contato',
                    'cpf' => '',
                    'rg' => '',
                    'data_nascimento' => '',
                    'telefone_1' => '',
                    'telefone_2' => '',
                    'email' => '',
                    'pontos' => '',
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'nome' => 'Cliente Sem Contato',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );
    }

    public function test_atualiza_cliente_apenas_com_nome(): void
    {
        $cliente = $this->criarCliente();

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Nome Atualizado',
                ]
            );

        $response->assertRedirect(
            route('clientes.index')
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'nome' => 'Nome Atualizado',
            ]
        );

        $this->assertDatabaseHas(
            'clientes',
            [
                'id' => $cliente->id,
                'pontos' => 0,
            ]
        );
    }

    public function test_permite_remover_dados_opcionais_existentes(): void
    {
        $cliente = $this->criarCliente([
            'cpf' => '12345678901',
            'rg' => '123456789',
            'data_nascimento' => '1990-01-10',
            'telefone_1' => '75999999999',
            'telefone_2' => '7533334444',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente Atualizado',
                    'cpf' => '',
                    'rg' => '',
                    'data_nascimento' => '',
                    'telefone_1' => '',
                    'telefone_2' => '',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'nome' => 'Cliente Atualizado',
                'cpf' => null,
                'rg' => null,
                'data_nascimento' => null,
                'telefone_1' => null,
                'telefone_2' => null,
            ]
        );
    }

    public function test_nao_permite_cpf_duplicado_na_atualizacao(): void
    {
        $this->criarCliente([
            'nome' => 'Primeiro Cliente',
            'cpf' => '12345678901',
        ]);

        $cliente = $this->criarCliente([
            'nome' => 'Segundo Cliente',
            'cpf' => '98765432100',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->from(
                route(
                    'clientes.edit',
                    $cliente->id
                )
            )
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Segundo Cliente',
                    'cpf' => '123.456.789-01',
                    'pontos' => 0,
                ]
            );

        $response
            ->assertRedirect(
                route(
                    'clientes.edit',
                    $cliente->id
                )
            )
            ->assertSessionHasErrors('cpf');

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $cliente->pessoa_id,
                'cpf' => '98765432100',
            ]
        );
    }

    public function test_atualiza_email_quando_cliente_possui_usuario(): void
    {
        $cliente = $this->criarCliente([
            'nome' => 'Cliente com Usuário',
        ]);

        $usuario = User::factory()->create([
            'pessoa_id' => $cliente->pessoa_id,
            'email' => 'antigo@example.com',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente com Usuário',
                    'email' => 'novo@example.com',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $usuario->id,
                'email' => 'novo@example.com',
            ]
        );
    }

    public function test_cliente_sem_usuario_nao_cria_usuario_ao_atualizar(): void
    {
        $cliente = $this->criarCliente([
            'nome' => 'Cliente sem Usuário',
        ]);

        $response = $this
            ->actingAs($this->admin)
            ->put(
                route(
                    'clientes.update',
                    $cliente->id
                ),
                [
                    'nome' => 'Cliente sem Usuário',
                    'email' => 'ignorado@example.com',
                    'pontos' => 0,
                ]
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing(
            'users',
            [
                'pessoa_id' => $cliente->pessoa_id,
            ]
        );
    }

    public function test_cria_usuario_de_acesso_sem_coluna_name(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->post(
                route('clientes.store'),
                [
                    'nome' => 'Cliente com Acesso',
                    'email' => 'cliente@example.com',
                    'criar_usuario' => 1,
                ]
            );

        $response->assertSessionHasNoErrors();

        $pessoa = Pessoa::where(
            'nome',
            'Cliente com Acesso'
        )->firstOrFail();

        $this->assertDatabaseHas(
            'users',
            [
                'pessoa_id' => $pessoa->id,
                'email' => 'cliente@example.com',
            ]
        );
    }
}
