<?php

namespace Tests\Feature\PortalCliente;

use App\Models\Endereco;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnderecosClienteTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $perfil = User::PERFIL_CLIENTE): User
    {
        return User::factory()->create([
            'perfil' => $perfil,
            'status' => true,
        ]);
    }

    private function dados(array $alterar = []): array
    {
        return array_merge([
            'cep' => '44000-000',
            'estado' => 'BA',
            'cidade' => 'Feira de Santana',
            'bairro' => 'Centro',
            'rua' => 'Rua Teste',
            'numero' => '100',
            'ponto_referencia' => 'Perto do mercado',
        ], $alterar);
    }

    private function endereco(
        User $usuario,
        array $dados = []
    ): Endereco {
        return $usuario->pessoa->enderecos()->create(
            $this->dados($dados)
        );
    }

    public function test_visitante_nao_acessa_enderecos(): void
    {
        $this->get(route('cliente.enderecos.index'))
            ->assertRedirect(route('erro-autenticacao'));

        $this->get(route('cliente.enderecos.create'))
            ->assertRedirect(route('erro-autenticacao'));

        $this->post(route('cliente.enderecos.store'), $this->dados())
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_admin_e_colaborador_nao_acessam_portal(): void
    {
        foreach ([
            User::PERFIL_ADMIN,
            User::PERFIL_COLABORADOR,
        ] as $perfil) {
            $usuario = $this->usuario($perfil);

            $this->actingAs($usuario)
                ->get(route('cliente.enderecos.index'))
                ->assertForbidden();

            $this->get(route('cliente.enderecos.create'))
                ->assertForbidden();

            $this->post(
                route('cliente.enderecos.store'),
                $this->dados()
            )->assertForbidden();
        }
    }

    public function test_cliente_enxerga_so_proprios_enderecos(): void
    {
        $usuario = $this->usuario();
        $outro = $this->usuario();

        $this->endereco($usuario, [
            'rua' => 'RUA PROPRIA AUTORIZADA',
        ]);

        $this->endereco($outro, [
            'rua' => 'RUA DE TERCEIRO SECRETA',
        ]);

        $this->actingAs($usuario)
            ->get(route('cliente.enderecos.index'))
            ->assertOk()
            ->assertSee('RUA PROPRIA AUTORIZADA')
            ->assertDontSee('RUA DE TERCEIRO SECRETA');
    }

    public function test_cliente_acessa_formulario_de_cadastro(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('cliente.enderecos.create'))
            ->assertOk()
            ->assertSee('Cadastrar endereço')
            ->assertDontSee('pessoa_id');
    }

    public function test_cadastro_ignora_tentativa_de_trocar_proprietario(): void
    {
        $usuario = $this->usuario();
        $outro = $this->usuario();

        $this->actingAs($usuario)
            ->post(route('cliente.enderecos.store'), array_merge(
                $this->dados(['rua' => 'RUA CADASTRADA PELO TITULAR']),
                ['pessoa_id' => $outro->pessoa_id]
            ))
            ->assertRedirect(route('cliente.enderecos.index'));

        $this->assertDatabaseHas('enderecos', [
            'pessoa_id' => $usuario->pessoa_id,
            'rua' => 'RUA CADASTRADA PELO TITULAR',
        ]);

        $this->assertDatabaseMissing('enderecos', [
            'pessoa_id' => $outro->pessoa_id,
            'rua' => 'RUA CADASTRADA PELO TITULAR',
        ]);
    }

    public function test_cliente_edita_apenas_proprio_endereco(): void
    {
        $usuario = $this->usuario();
        $outro = $this->usuario();

        $proprio = $this->endereco($usuario);
        $alheio = $this->endereco($outro);

        $this->actingAs($usuario)
            ->get(route('cliente.enderecos.edit', $proprio->id))
            ->assertOk();

        $this->put(
            route('cliente.enderecos.update', $proprio->id),
            array_merge(
                $this->dados(['rua' => 'RUA NOVA']),
                ['pessoa_id' => $outro->pessoa_id]
            )
        )->assertRedirect(route('cliente.enderecos.index'));

        $this->assertSame('RUA NOVA', $proprio->fresh()->rua);
        $this->assertSame(
            $usuario->pessoa_id,
            (int) $proprio->fresh()->pessoa_id
        );

        $this->assertSame('Rua Teste', $alheio->fresh()->rua);
    }

    public function test_endereco_alheio_retorna_404_na_edicao(): void
    {
        $usuario = $this->usuario();
        $outro = $this->usuario();
        $alheio = $this->endereco($outro, [
            'rua' => 'SEGREDO ENDERECO ALHEIO',
        ]);

        $this->actingAs($usuario)
            ->get(route('cliente.enderecos.edit', $alheio->id))
            ->assertNotFound();

        $this->put(
            route('cliente.enderecos.update', $alheio->id),
            $this->dados(['rua' => 'TENTATIVA DE INVASAO'])
        )->assertNotFound();

        $this->assertSame(
            'SEGREDO ENDERECO ALHEIO',
            $alheio->fresh()->rua
        );
    }

    public function test_normaliza_cep_e_estado(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->post(
                route('cliente.enderecos.store'),
                $this->dados([
                    'cep' => '44000000',
                    'estado' => 'ba',
                ])
            )
            ->assertRedirect(route('cliente.enderecos.index'));

        $this->assertDatabaseHas('enderecos', [
            'pessoa_id' => $usuario->pessoa_id,
            'cep' => '44000-000',
            'estado' => 'BA',
        ]);
    }

    public function test_rejeita_dados_invalidos_sem_gravar(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->post(
                route('cliente.enderecos.store'),
                $this->dados([
                    'cep' => '123',
                    'estado' => 'XX',
                    'cidade' => str_repeat('A', 51),
                    'bairro' => str_repeat('B', 51),
                    'rua' => str_repeat('C', 101),
                    'numero' => str_repeat('D', 21),
                    'ponto_referencia' => str_repeat('E', 201),
                ])
            )
            ->assertSessionHasErrors([
                'cep',
                'estado',
                'cidade',
                'bairro',
                'rua',
                'numero',
                'ponto_referencia',
            ]);

        $this->assertDatabaseCount('enderecos', 0);
    }

    public function test_ponto_de_referencia_e_opcional(): void
    {
        $usuario = $this->usuario();

        $dados = $this->dados();
        unset($dados['ponto_referencia']);

        $this->actingAs($usuario)
            ->post(route('cliente.enderecos.store'), $dados)
            ->assertRedirect(route('cliente.enderecos.index'));

        $this->assertDatabaseHas('enderecos', [
            'pessoa_id' => $usuario->pessoa_id,
            'ponto_referencia' => null,
        ]);
    }

    public function test_paginacao_limita_dez_enderecos(): void
    {
        $usuario = $this->usuario();

        for ($i = 1; $i <= 11; $i++) {
            $this->endereco($usuario, [
                'rua' => sprintf('RUA TESTE %02d', $i),
            ]);
        }

        $this->actingAs($usuario)
            ->get(route('cliente.enderecos.index'))
            ->assertOk()
            ->assertSee('RUA TESTE 01')
            ->assertDontSee('RUA TESTE 11');

        $this->get(route('cliente.enderecos.index', [
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('RUA TESTE 11')
            ->assertDontSee('RUA TESTE 01');
    }

    public function test_exclusao_do_cliente_nao_foi_habilitada(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has(
                'cliente.enderecos.destroy'
            )
        );
    }

    public function test_rotas_administrativas_seguem_protegidas(): void
    {
        $usuario = $this->usuario();
        $endereco = $this->endereco($usuario);

        $this->actingAs($usuario)
            ->get(route('enderecos.index'))
            ->assertForbidden();

        $this->get(route('enderecos.edit', $endereco->id))
            ->assertForbidden();

        $this->delete(route('enderecos.destroy', $endereco->id))
            ->assertForbidden();

        $this->assertDatabaseHas('enderecos', [
            'id' => $endereco->id,
        ]);
    }

    public function test_cliente_bloqueado_nao_acessa_enderecos(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['status' => false]);

        $this->actingAs($usuario)
            ->get(route('cliente.enderecos.index'))
            ->assertRedirect(route('login'));
    }
}
