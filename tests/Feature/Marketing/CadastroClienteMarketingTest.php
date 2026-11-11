<?php

namespace Tests\Feature\Marketing;

use App\Mail\Marketing\ConfirmarCadastroCliente;
use App\Models\MarketingCadastroPendente;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CadastroClienteMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('marketing.cadastro_cliente_habilitado', true);
    }

    private function dados(array $alteracoes = []): array
    {
        return array_merge([
            'nome' => 'Cliente do Site',
            'email' => 'novo@example.test',
            'whatsapp' => '(75) 99999-0000',
            'senha' => 'SenhaSegura12345',
            'senha_confirmation' => 'SenhaSegura12345',
            'aceite_privacidade' => '1',
        ], $alteracoes);
    }

    public function test_cadastro_cria_cliente_apenas_apos_confirmacao_de_email(): void
    {
        Mail::fake();

        $this->get(route('marketing.cadastro.create'))
            ->assertOk()->assertSee('Criar minha conta');

        $this->post(route('marketing.cadastro.store'), $this->dados())
            ->assertRedirect(route('marketing.cadastro.create'))
            ->assertSessionHas('marketing_cadastro_enviado');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('marketing_cadastros_pendentes', 1);
        $this->assertNotSame(
            'SenhaSegura12345', MarketingCadastroPendente::firstOrFail()->senha_hash
        );

        $url = null;
        Mail::assertSent(ConfirmarCadastroCliente::class, function ($mail) use (&$url) {
            $url = $mail->url;
            return $mail->hasTo('novo@example.test');
        });
        $this->assertNotNull($url);
        $this->get($url)->assertRedirect(route('login'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('pessoas', 1);
        $this->assertDatabaseCount('marketing_cadastros_pendentes', 0);
        $user = User::firstOrFail();
        $this->assertSame(User::PERFIL_CLIENTE, $user->perfil);
        $this->assertTrue($user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($user->pessoa_id, $user->cliente->pessoa_id);
        $this->get($url)->assertForbidden();
    }

    public function test_email_existente_nao_gera_nova_conta_nem_revela_existencia(): void
    {
        Mail::fake();
        $pessoa = Pessoa::create(['nome' => 'Cliente Antigo', 'telefone_1' => '75911111111']);
        User::create([
            'pessoa_id' => $pessoa->id,
            'email' => 'novo@example.test',
            'password' => bcrypt('OutroSegredo123'),
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $this->post(route('marketing.cadastro.store'), $this->dados())
            ->assertRedirect(route('marketing.cadastro.create'))
            ->assertSessionHas('marketing_cadastro_enviado');

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('marketing_cadastros_pendentes', 0);
        Mail::assertNothingSent();
    }

    public function test_dados_invalidos_nao_disparam_email(): void
    {
        Mail::fake();

        $this->post(route('marketing.cadastro.store'), $this->dados([
            'senha' => 'fraca',
            'senha_confirmation' => 'fraca',
            'aceite_privacidade' => null,
            'website' => 'spam',
        ]))->assertSessionHasErrors(['senha', 'aceite_privacidade', 'website']);

        $this->assertDatabaseCount('marketing_cadastros_pendentes', 0);
        Mail::assertNothingSent();
    }

    public function test_links_alterados_e_reutilizados_sao_rejeitados(): void
    {
        Mail::fake();
        $this->post(route('marketing.cadastro.store'), $this->dados());
        $url = null;
        Mail::assertSent(ConfirmarCadastroCliente::class, function ($mail) use (&$url) {
            $url = $mail->url;
            return true;
        });
        $this->get($url.'&token=outro')->assertForbidden();
        $this->assertDatabaseCount('users', 0);
        $this->get($url)->assertRedirect(route('login'));
        $this->get($url)->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_cadastro_desabilitado_nao_cria_registros(): void
    {
        Mail::fake();
        config()->set('marketing.cadastro_cliente_habilitado', false);
        $this->get(route('marketing.cadastro.create'))->assertNotFound();
        $this->post(route('marketing.cadastro.store'), $this->dados())->assertNotFound();
        $this->assertDatabaseCount('marketing_cadastros_pendentes', 0);
        Mail::assertNothingSent();
    }
}
