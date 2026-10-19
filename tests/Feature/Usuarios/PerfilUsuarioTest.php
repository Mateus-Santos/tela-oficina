<?php

namespace Tests\Feature\Usuarios;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_pode_visualizar_o_proprio_perfil(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $response = $this
            ->actingAs($cliente)
            ->get(
                route('perfil')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'user',
                fn (User $user) => $user->is($cliente)
            );
    }

    public function test_colaborador_pode_visualizar_o_proprio_perfil(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $response = $this
            ->actingAs($colaborador)
            ->get(
                route('perfil')
            );

        $response
            ->assertOk()
            ->assertViewHas(
                'user',
                fn (User $user) => $user->is($colaborador)
            );
    }

    public function test_cliente_atualiza_apenas_o_proprio_perfil(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $outroUsuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $emailOutro =
            $outroUsuario->email;

        $response = $this
            ->actingAs($cliente)
            ->put(
                route('perfil.update'),
                [
                    'nome' => 'Nome Atualizado',
                    'email' => 'novo@email.com',
                    'cpf' => '12345678901',
                    'rg' => '1234567',
                    'telefone_1' => '75999999999',
                    'telefone_2' => '75888888888',
                    'data_nascimento' => '1990-01-01',
                ]
            );

        $response->assertRedirect(
            route('perfil')
        );

        $cliente->refresh();
        $outroUsuario->refresh();

        $this->assertSame(
            'novo@email.com',
            $cliente->email
        );

        $this->assertSame(
            'Nome Atualizado',
            $cliente->pessoa->fresh()->nome
        );

        $this->assertSame(
            $emailOutro,
            $outroUsuario->email
        );
    }

    public function test_perfil_nao_permite_alterar_status(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($cliente)
            ->put(
                route('perfil.update'),
                [
                    'nome' => $cliente->pessoa->nome,
                    'email' => $cliente->email,
                    'status' => false,
                ]
            )
            ->assertRedirect(
                route('perfil')
            );

        $this->assertTrue(
            (bool) $cliente->fresh()->status
        );
    }

    public function test_perfil_nao_permite_alterar_perfil_de_acesso(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($cliente)
            ->put(
                route('perfil.update'),
                [
                    'nome' => $cliente->pessoa->nome,
                    'email' => $cliente->email,
                    'perfil' => User::PERFIL_ADMIN,
                ]
            )
            ->assertRedirect(
                route('perfil')
            );

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $cliente->fresh()->perfil
        );
    }

    public function test_perfil_nao_permite_alterar_pontos_do_cliente(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $clienteModel =
            Cliente::create([
                'pessoa_id' => $cliente->pessoa_id,
                'pontos' => 10,
            ]);

        $this
            ->actingAs($cliente)
            ->put(
                route('perfil.update'),
                [
                    'nome' => $cliente->pessoa->nome,
                    'email' => $cliente->email,
                    'pontos' => 999999,
                ]
            )
            ->assertRedirect(
                route('perfil')
            );

        $this->assertSame(
            10,
            $clienteModel->fresh()->pontos
        );
    }

    public function test_visitante_nao_pode_acessar_perfil(): void
    {
        $this
            ->get(
                route('perfil')
            )
            ->assertRedirect(
                route('erro-autenticacao')
            );
    }

    public function test_visitante_nao_pode_atualizar_perfil(): void
    {
        $this
            ->put(
                route('perfil.update'),
                []
            )
            ->assertRedirect(
                route('erro-autenticacao')
            );
    }

    private function criarUsuario(
        string $perfil
    ): User {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => true,
        ]);
    }
}
