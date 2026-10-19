<?php

namespace Tests\Feature\Colaboradores;

use App\Models\Colaborador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColaboradorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cria_colaborador_e_altera_perfil_do_usuario(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $response = $this
            ->actingAs($admin)
            ->post(
                route('colaboradores.store'),
                [
                    'id_user' => $usuario->id,
                    'chave_pix' => 'pix-colaborador-1',
                    'conta_banco' => '12345-6',
                ]
            );

        $response->assertRedirect(
            route('colaboradores.index')
        );

        $this->assertDatabaseHas(
            'colaboradores',
            [
                'id_user' => $usuario->id,
                'chave_pix' => 'pix-colaborador-1',
                'conta_banco' => '12345-6',
            ]
        );

        $this->assertSame(
            User::PERFIL_COLABORADOR,
            $usuario->fresh()->perfil
        );
    }

    public function test_nao_permite_transformar_admin_em_colaborador(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $outroAdmin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $response = $this
            ->actingAs($admin)
            ->from(
                route('colaboradores.create')
            )
            ->post(
                route('colaboradores.store'),
                [
                    'id_user' => $outroAdmin->id,
                    'chave_pix' => 'pix-admin',
                    'conta_banco' => '12345-6',
                ]
            );

        $response
            ->assertRedirect(
                route('colaboradores.create')
            )
            ->assertSessionHasErrors(
                'id_user'
            );

        $this->assertDatabaseMissing(
            'colaboradores',
            [
                'id_user' => $outroAdmin->id,
            ]
        );

        $this->assertSame(
            User::PERFIL_ADMIN,
            $outroAdmin->fresh()->perfil
        );
    }

    public function test_nao_permite_dois_colaboradores_para_o_mesmo_usuario(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        Colaborador::create([
            'id_user' => $usuario->id,
            'chave_pix' => 'pix-original',
            'conta_banco' => '11111-1',
        ]);

        $usuario->update([
            'perfil' => User::PERFIL_COLABORADOR,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(
                route('colaboradores.create')
            )
            ->post(
                route('colaboradores.store'),
                [
                    'id_user' => $usuario->id,
                    'chave_pix' => 'pix-novo',
                    'conta_banco' => '22222-2',
                ]
            );

        $response
            ->assertRedirect(
                route('colaboradores.create')
            )
            ->assertSessionHasErrors(
                'id_user'
            );

        $this->assertSame(
            1,
            Colaborador::query()
                ->where(
                    'id_user',
                    $usuario->id
                )
                ->count()
        );
    }

    public function test_atualizacao_pode_trocar_usuario_do_colaborador(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuarioAnterior = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $novoUsuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $colaborador = Colaborador::create([
            'id_user' => $usuarioAnterior->id,
            'chave_pix' => 'pix-antigo',
            'conta_banco' => '11111-1',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(
                route(
                    'colaboradores.update',
                    $colaborador->id
                ),
                [
                    'id_user' => $novoUsuario->id,
                    'chave_pix' => 'pix-novo',
                    'conta_banco' => '22222-2',
                ]
            );

        $response->assertRedirect(
            route('colaboradores.index')
        );

        $this->assertDatabaseHas(
            'colaboradores',
            [
                'id' => $colaborador->id,
                'id_user' => $novoUsuario->id,
                'chave_pix' => 'pix-novo',
                'conta_banco' => '22222-2',
            ]
        );

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuarioAnterior->fresh()->perfil
        );

        $this->assertSame(
            User::PERFIL_COLABORADOR,
            $novoUsuario->fresh()->perfil
        );
    }

    public function test_excluir_colaborador_retorna_usuario_para_cliente(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $colaborador = Colaborador::create([
            'id_user' => $usuario->id,
            'chave_pix' => 'pix-exclusao',
            'conta_banco' => '12345-6',
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete(
                route(
                    'colaboradores.destroy',
                    $colaborador->id
                )
            );

        $response->assertRedirect(
            route('colaboradores.index')
        );

        $this->assertDatabaseMissing(
            'colaboradores',
            [
                'id' => $colaborador->id,
            ]
        );

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuario->fresh()->perfil
        );
    }

    public function test_cliente_nao_pode_administrar_colaboradores(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $response = $this
            ->actingAs($cliente)
            ->get(
                route('colaboradores.index')
            );

        $response->assertForbidden();
    }

    public function test_colaborador_nao_pode_administrar_colaboradores(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $response = $this
            ->actingAs($colaborador)
            ->get(
                route('colaboradores.index')
            );

        $response->assertForbidden();
    }

    public function test_admin_pode_acessar_gestao_de_colaboradores(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                route('colaboradores.index')
            );

        $response->assertOk();
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
