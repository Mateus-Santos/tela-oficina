<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/home');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_usuario_bloqueado_perde_sessao_web(): void
    {
        $usuario = User::factory()->create(['status' => false]);

        $this->actingAs($usuario)
            ->withSession(['marcador_sensivel' => 'valor'])
            ->get(route('perfil'))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('marcador_sensivel');

        $this->assertGuest();
    }

    public function test_usuario_bloqueado_nao_acessa_api_com_token(): void
    {
        $usuario = User::factory()->create(['status' => false]);
        $token = $usuario->createToken('teste-bloqueio')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertForbidden()
            ->assertDontSee($usuario->email);
    }

    public function test_token_de_usuario_ativo_continua_funcional(): void
    {
        $usuario = User::factory()->create(['status' => true]);
        $token = $usuario->createToken('teste-ativo')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $usuario->id);
    }
}
