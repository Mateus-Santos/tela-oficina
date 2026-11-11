<?php

namespace Tests\Feature\PortalCliente;

use App\Models\Cliente;
use App\Models\Montadora;
use App\Models\User;
use App\Models\Veiculo;
use App\Models\VeiculosCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeiculosPortalTest extends TestCase
{
    use RefreshDatabase;

    private int $sequencia = 0;

    private function cliente(string $nome): array
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        $usuario->pessoa->update(['nome' => $nome]);

        $cliente = Cliente::create([
            'pessoa_id' => $usuario->pessoa_id,
            'pontos' => 0,
        ]);

        return [$usuario, $cliente];
    }

    private function veiculo(
        Cliente $cliente,
        string $placa
    ): VeiculosCliente {
        $this->sequencia++;

        $montadora = Montadora::create([
            'nome' => 'MONTADORA PORTAL '.$this->sequencia,
        ]);

        $modelo = Veiculo::create([
            'nome' => 'MODELO PORTAL '.$this->sequencia,
            'montadora_id' => $montadora->id,
        ]);

        $item = VeiculosCliente::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $modelo->id,
            'placa' => $placa,
            'ano' => 2024,
            'cor' => 'Branco',
        ]);

        $item->clientes()->attach($cliente->id);

        return $item;
    }

    private function dados(
        VeiculosCliente $item
    ): array {
        return [
            'veiculo_id' => $item->veiculo_id,
            'placa' => $item->placa,
            'ano' => $item->ano,
            'cor' => 'Azul',
        ];
    }

    public function test_visitante_nao_ve_portal(): void
    {
        $this->get(route('cliente.veiculos.index'))
            ->assertRedirect(route('erro-autenticacao'));

        $this->get(route('cliente.veiculos.show', 1))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_admin_e_colaborador_nao_entram_no_portal(): void
    {
        foreach ([
            User::PERFIL_ADMIN,
            User::PERFIL_COLABORADOR,
        ] as $perfil) {
            $usuario = User::factory()->create([
                'perfil' => $perfil,
                'status' => true,
            ]);

            $this->actingAs($usuario)
                ->get(route('cliente.veiculos.index'))
                ->assertForbidden();

            $this->get(route('cliente.veiculos.show', 1))
                ->assertForbidden();
        }
    }

    public function test_cliente_ve_somente_seus_veiculos(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR AUTORIZADO');
        [, $outro] = $this->cliente('RESPONSAVEL ALHEIO SECRETO');

        $proprio = $this->veiculo($cliente, 'ABC1D23');
        $alheio = $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario);

        foreach ([
            route('cliente.veiculos.index'),
            route('veiculosclientes.index'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($proprio->placa)
                ->assertDontSee($alheio->placa)
                ->assertDontSee('RESPONSAVEL ALHEIO SECRETO');
        }
    }

    public function test_veiculo_compartilhado_oculta_outro_responsavel(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR AUTORIZADO');
        [, $outro] = $this->cliente('PESSOA COMPARTILHADA SECRETA');

        $item = $this->veiculo($cliente, 'ABC1D23');
        $item->clientes()->attach($outro->id);

        $this->actingAs($usuario);

        foreach ([
            route('cliente.veiculos.index'),
            route('veiculosclientes.index'),
            route('cliente.veiculos.show', $item->id),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('ABC1D23')
                ->assertDontSee('PESSOA COMPARTILHADA SECRETA');
        }
    }

    public function test_detalhe_de_terceiro_retorna_404(): void
    {
        [$usuario] = $this->cliente('PRIMEIRO');
        [, $outro] = $this->cliente('SEGUNDO');

        $item = $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario)
            ->get(route('cliente.veiculos.show', $item->id))
            ->assertNotFound();
    }

    public function test_cliente_pode_editar_veiculo_exclusivo(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        $item = $this->veiculo($cliente, 'ABC1D23');

        $this->actingAs($usuario)
            ->get(route('veiculosclientes.edit', $item->id))
            ->assertOk();

        $this->patch(
            route('veiculosclientes.update', $item->id),
            $this->dados($item)
        )->assertRedirect(route('veiculosclientes.index'));

        $this->assertSame('Azul', $item->fresh()->cor);
    }

    public function test_cliente_nao_altera_responsaveis_por_payload(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        [, $outro] = $this->cliente('TERCEIRO');

        $item = $this->veiculo($cliente, 'ABC1D23');

        $this->actingAs($usuario)->patch(
            route('veiculosclientes.update', $item->id),
            array_merge($this->dados($item), [
                'clientes' => [$outro->id],
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('cliente_veiculo_cliente', [
            'veiculo_cliente_id' => $item->id,
            'cliente_id' => $cliente->id,
        ]);

        $this->assertDatabaseMissing('cliente_veiculo_cliente', [
            'veiculo_cliente_id' => $item->id,
            'cliente_id' => $outro->id,
        ]);
    }

    public function test_cliente_nao_edita_veiculo_compartilhado(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        [, $outro] = $this->cliente('SEGUNDO');

        $item = $this->veiculo($cliente, 'ABC1D23');
        $item->clientes()->attach($outro->id);

        $this->actingAs($usuario)
            ->get(route('veiculosclientes.edit', $item->id))
            ->assertForbidden();

        $this->patch(
            route('veiculosclientes.update', $item->id),
            $this->dados($item)
        )->assertForbidden();

        $this->assertSame('Branco', $item->fresh()->cor);
    }

    public function test_cliente_nao_exclui_veiculo_exclusivo_ou_compartilhado(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        [, $outro] = $this->cliente('SEGUNDO');

        $exclusivo = $this->veiculo($cliente, 'ABC1D23');
        $compartilhado = $this->veiculo($cliente, 'DEF4G56');
        $compartilhado->clientes()->attach($outro->id);

        $this->actingAs($usuario);

        foreach ([$exclusivo, $compartilhado] as $item) {
            $this->delete(
                route('veiculosclientes.destroy', $item->id)
            )->assertForbidden();

            $this->assertDatabaseHas('veiculos_clientes', [
                'id' => $item->id,
            ]);
        }
    }

    public function test_cliente_nao_exclui_veiculo_de_terceiro(): void
    {
        [$usuario] = $this->cliente('PRIMEIRO');
        [, $outro] = $this->cliente('SEGUNDO');

        $item = $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario)
            ->delete(route('veiculosclientes.destroy', $item->id))
            ->assertNotFound();
    }

    public function test_cliente_pode_cadastrar_veiculo_proprio(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        [, $outro] = $this->cliente('OUTRO');

        $modelo = $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario)
            ->post(route('veiculosclientes.store'), [
                'veiculo_id' => $modelo->veiculo_id,
                'placa' => 'ABC1D23',
                'ano' => 2023,
                'cor' => 'Prata',
                'clientes' => [$outro->id],
            ])
            ->assertRedirect(route('veiculosclientes.index'));

        $novo = VeiculosCliente::where('placa', 'ABC1D23')
            ->firstOrFail();

        $this->assertDatabaseHas('cliente_veiculo_cliente', [
            'veiculo_cliente_id' => $novo->id,
            'cliente_id' => $cliente->id,
        ]);

        $this->assertDatabaseMissing('cliente_veiculo_cliente', [
            'veiculo_cliente_id' => $novo->id,
            'cliente_id' => $outro->id,
        ]);
    }

    public function test_usuario_sem_cliente_tem_lista_vazia(): void
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        [, $outro] = $this->cliente('OUTRO');
        $item = $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario)
            ->get(route('cliente.veiculos.index'))
            ->assertOk()
            ->assertSee('Nenhum veículo encontrado')
            ->assertDontSee('DEF4G56');

        $this->get(route('cliente.veiculos.show', $item->id))
            ->assertNotFound();
    }

    public function test_busca_por_placa_nao_altera_escopo(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');
        [, $outro] = $this->cliente('OUTRO');

        $this->veiculo($cliente, 'ABC1D23');
        $this->veiculo($outro, 'DEF4G56');

        $this->actingAs($usuario)
            ->get(route('cliente.veiculos.index', [
                'placa' => 'ABC-1D23',
            ]))
            ->assertOk()
            ->assertSee('ABC1D23')
            ->assertDontSee('DEF4G56');

        $this->get(route('cliente.veiculos.index', [
            'placa' => 'DEF4G56',
        ]))
            ->assertOk()
            ->assertSee('Nenhum veículo encontrado');
    }

    public function test_paginacao_limita_doze_registros(): void
    {
        [$usuario, $cliente] = $this->cliente('TITULAR');

        for ($i = 1; $i <= 13; $i++) {
            $this->veiculo(
                $cliente,
                sprintf('ABC%04d', $i)
            );
        }

        $this->actingAs($usuario)
            ->get(route('cliente.veiculos.index'))
            ->assertOk()
            ->assertSee('ABC0001')
            ->assertDontSee('ABC0013');

        $this->get(route('cliente.veiculos.index', [
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee('ABC0013')
            ->assertDontSee('ABC0001');
    }

    public function test_admin_conserva_listagem_operacional(): void
    {
        $admin = User::factory()->create([
            'perfil' => User::PERFIL_ADMIN,
            'status' => true,
        ]);

        [, $cliente] = $this->cliente('RESPONSAVEL ADMIN');

        $this->veiculo($cliente, 'ABC1D23');

        $this->actingAs($admin)
            ->get(route('veiculosclientes.index'))
            ->assertOk()
            ->assertSee('RESPONSAVEL ADMIN')
            ->assertSee('ABC1D23');
    }

    public function test_cliente_bloqueado_nao_acessa_veiculos(): void
    {
        [$usuario] = $this->cliente('BLOQUEADO');
        $usuario->update(['status' => false]);

        $this->actingAs($usuario)
            ->get(route('cliente.veiculos.index'))
            ->assertRedirect(route('login'));
    }
}
