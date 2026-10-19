<?php

namespace Tests\Feature\Usuarios;

use App\Livewire\ToggleSwitch;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToggleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pode_bloquear_outro_usuario(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        Livewire::actingAs($admin)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $usuario,
                    'field' => 'status',
                ]
            )
            ->set('isActive', false)
            ->assertSuccessful();

        $this->assertFalse(
            (bool) $usuario->fresh()->status
        );
    }

    public function test_admin_pode_desbloquear_outro_usuario(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE,
            false
        );

        Livewire::actingAs($admin)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $usuario,
                    'field' => 'status',
                ]
            )
            ->set('isActive', true)
            ->assertSuccessful();

        $this->assertTrue(
            (bool) $usuario->fresh()->status
        );
    }

    public function test_admin_nao_pode_bloquear_a_propria_conta(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        Livewire::actingAs($admin)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $admin,
                    'field' => 'status',
                ]
            )
            ->set('isActive', false)
            ->assertForbidden();

        $this->assertTrue(
            (bool) $admin->fresh()->status
        );
    }

    public function test_cliente_nao_pode_usar_toggle_de_usuario(): void
    {
        $cliente = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        Livewire::actingAs($cliente)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $usuario,
                    'field' => 'status',
                ]
            )
            ->assertForbidden();

        $this->assertTrue(
            (bool) $usuario->fresh()->status
        );
    }

    public function test_colaborador_nao_pode_usar_toggle_de_usuario(): void
    {
        $colaborador = $this->criarUsuario(
            User::PERFIL_COLABORADOR
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        Livewire::actingAs($colaborador)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $usuario,
                    'field' => 'status',
                ]
            )
            ->assertForbidden();

        $this->assertTrue(
            (bool) $usuario->fresh()->status
        );
    }

    public function test_toggle_nao_aceita_outro_campo(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $usuario = $this->criarUsuario(
            User::PERFIL_CLIENTE
        );

        Livewire::actingAs($admin)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $usuario,
                    'field' => 'perfil',
                ]
            )
            ->assertForbidden();

        $this->assertSame(
            User::PERFIL_CLIENTE,
            $usuario->fresh()->perfil
        );
    }

    public function test_toggle_nao_aceita_outro_model(): void
    {
        $admin = $this->criarUsuario(
            User::PERFIL_ADMIN
        );

        $pessoa = Pessoa::factory()->create();

        Livewire::actingAs($admin)
            ->test(
                ToggleSwitch::class,
                [
                    'model' => $pessoa,
                    'field' => 'status',
                ]
            )
            ->assertForbidden();
    }

    private function criarUsuario(
        string $perfil,
        bool $status = true
    ): User {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => $status,
        ]);
    }
}
