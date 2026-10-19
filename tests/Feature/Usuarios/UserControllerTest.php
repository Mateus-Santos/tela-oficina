<?php

namespace Tests\Feature\Usuarios;

use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cria_usuario_cliente_sem_senha_padrao(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('users.store'),
                [
                    'nome' => 'Cliente Teste',
                    'email' => 'cliente.teste@example.com',
                    'data_nascimento' => '1990-01-01',
                    'telefone_1' => '(75) 99999-9999',
                ]
            );

        $response->assertRedirect(
            route('users.index')
        );

        $pessoa = Pessoa::query()
            ->where(
                'nome',
                'Cliente Teste'
            )
            ->firstOrFail();

        $usuario = User::query()
            ->where(
                'email',
                'cliente.teste@example.com'
            )
            ->firstOrFail();

        $this->assertSame(
            $pessoa->id,
            $usuario->pessoa_id
        );

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuario->perfil
        );

        $this->assertSame(
            '75999999999',
            $pessoa->telefone_1
        );

        $this->assertFalse(
            Hash::check(
                '12345678',
                $usuario->password
            )
        );

        $this->assertTrue(
            str_starts_with(
                $usuario->password,
                '$2y$'
            )
            || str_starts_with(
                $usuario->password,
                '$argon2'
            )
        );
    }

    public function test_admin_pode_acessar_listagem_de_usuarios(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $this
            ->actingAs($admin)
            ->get(
                route('users.index')
            )
            ->assertOk();
    }

    public function test_cliente_nao_pode_acessar_listagem_de_usuarios(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($cliente)
            ->get(
                route('users.index')
            )
            ->assertForbidden();
    }

    public function test_colaborador_nao_pode_acessar_listagem_de_usuarios(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $this
            ->actingAs($colaborador)
            ->get(
                route('users.index')
            )
            ->assertForbidden();
    }

    public function test_admin_pode_editar_outro_usuario(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'users.update',
                    $usuario
                ),
                [
                    'nome' => 'Nome Atualizado',
                    'email' => 'atualizado@example.com',
                    'cpf' => '123.456.789-01',
                    'rg' => '12.345.678-9',
                    'telefone_1' => '(75) 99999-1111',
                    'telefone_2' => '(75) 98888-2222',
                    'data_nascimento' => '1995-05-10',
                ]
            );

        $response->assertRedirect(
            route('users.index')
        );

        $usuario->refresh();

        $this->assertSame(
            'atualizado@example.com',
            $usuario->email
        );

        $this->assertSame(
            'Nome Atualizado',
            $usuario->pessoa->fresh()->nome
        );

        $this->assertSame(
            '12345678901',
            $usuario->pessoa->fresh()->cpf
        );

        $this->assertSame(
            '123456789',
            $usuario->pessoa->fresh()->rg
        );

        $this->assertSame(
            '75999991111',
            $usuario->pessoa->fresh()->telefone_1
        );

        $this->assertSame(
            '75988882222',
            $usuario->pessoa->fresh()->telefone_2
        );

        $this->assertSame(
            '1995-05-10',
            $usuario->pessoa->fresh()->data_nascimento
        );
    }

    public function test_cliente_nao_pode_editar_usuario_pela_area_administrativa(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $emailOriginal = $usuario->email;

        $this
            ->actingAs($cliente)
            ->put(
                route(
                    'users.update',
                    $usuario
                ),
                [
                    'nome' => 'Tentativa Indevida',
                    'email' => 'ataque@example.com',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            $emailOriginal,
            $usuario->fresh()->email
        );
    }

    public function test_colaborador_nao_pode_editar_usuario_pela_area_administrativa(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $emailOriginal = $usuario->email;

        $this
            ->actingAs($colaborador)
            ->put(
                route(
                    'users.update',
                    $usuario
                ),
                [
                    'nome' => 'Tentativa Indevida',
                    'email' => 'ataque@example.com',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            $emailOriginal,
            $usuario->fresh()->email
        );
    }

    public function test_admin_pode_excluir_outro_usuario_sem_excluir_pessoa(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $pessoaId = $usuario->pessoa_id;
        $userId = $usuario->id;

        $response = $this
            ->actingAs($admin)
            ->delete(
                route(
                    'users.destroy',
                    $usuario
                )
            );

        $response->assertRedirect(
            route('users.index')
        );

        $this->assertDatabaseMissing(
            'users',
            [
                'id' => $userId,
            ]
        );

        $this->assertDatabaseHas(
            'pessoas',
            [
                'id' => $pessoaId,
            ]
        );
    }

    public function test_admin_nao_pode_excluir_a_propria_conta(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $this
            ->actingAs($admin)
            ->delete(
                route(
                    'users.destroy',
                    $admin
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $admin->id,
            ]
        );
    }

    public function test_cliente_nao_pode_excluir_usuario(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($cliente)
            ->delete(
                route(
                    'users.destroy',
                    $usuario
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $usuario->id,
            ]
        );
    }

    public function test_colaborador_nao_pode_excluir_usuario(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($colaborador)
            ->delete(
                route(
                    'users.destroy',
                    $usuario
                )
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $usuario->id,
            ]
        );
    }

    public function test_admin_nao_altera_perfil_pelo_update_generico(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'users.update',
                    $usuario
                ),
                [
                    'nome' => $usuario->pessoa->nome,
                    'email' => $usuario->email,
                    'perfil' => User::PERFIL_ADMIN,
                ]
            )
            ->assertRedirect(
                route('users.index')
            );

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuario->fresh()->perfil
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
