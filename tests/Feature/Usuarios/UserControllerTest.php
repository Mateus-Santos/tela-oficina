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
        $admin = $this->criarAdmin();

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

        $this->assertNotSame(
            '12345678',
            $usuario->password
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

    private function criarAdmin(): User
    {
        $pessoa = Pessoa::factory()->create();

        return User::factory()->create([
            'pessoa_id' => $pessoa->id,
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);
    }
}
