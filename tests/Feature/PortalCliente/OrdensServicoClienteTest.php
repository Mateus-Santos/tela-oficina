<?php

namespace Tests\Feature\PortalCliente;

use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\OrdemServico;
use App\Models\SetorServico;
use App\Models\User;
use App\Models\VeiculosCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrdensServicoClienteTest extends TestCase
{
    use RefreshDatabase;

    private int $sequencia = 0;

    private function cliente(): array
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

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
        $agora = now();

        $montadoraId = DB::table('montadoras')->insertGetId([
            'nome' => 'MONTADORA TESTE '.uniqid(),
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculoId = DB::table('veiculos')->insertGetId([
            'nome' => 'MODELO PORTAL OS',
            'montadora_id' => $montadoraId,
            'created_at' => $agora,
            'updated_at' => $agora,
        ]);

        $veiculo = VeiculosCliente::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculoId,
            'placa' => $placa,
            'ano' => 2024,
            'cor' => 'Branco',
        ]);

        $veiculo->clientes()->attach($cliente->id);

        return $veiculo;
    }

    private function ordem(
        Cliente $cliente,
        VeiculosCliente $veiculo,
        array $dados = []
    ): OrdemServico {
        $this->sequencia++;

        $setor = SetorServico::create([
            'setor' => 'MECANICA PORTAL '.$this->sequencia,
            'nivel' => '1',
        ]);

        $etapa = Etapa::query()
            ->where('slug', 'diagnostico')
            ->firstOrFail();

        return OrdemServico::create(array_merge([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculo->id,
            'setor_servico_id' => $setor->id,
            'etapa_id' => $etapa->id,
            'status' => 'aberta',
            'descricao' => 'SEGREDO_INTERNO_OS',
            'valor' => '230.55',
            'data_abertura' => now(),
        ], $dados));
    }

    public function test_visitante_nao_acessa_portal_de_os(): void
    {
        $this->get(route('cliente.os.index'))
            ->assertRedirect(route('erro-autenticacao'));

        $this->get(route('cliente.os.show', 1))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_admin_e_colaborador_nao_acessam_os_do_cliente(): void
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
                ->get(route('cliente.os.index'))
                ->assertForbidden();

            $this->get(route('cliente.os.show', 1))
                ->assertForbidden();
        }
    }

    public function test_cliente_ve_somente_as_proprias_os(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $veiculoA = $this->veiculo($cliente, 'ABC1D23');
        $veiculoB = $this->veiculo($outro, 'DEF4G56');

        $propria = $this->ordem($cliente, $veiculoA);
        $alheia = $this->ordem($outro, $veiculoB);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index'))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $propria->id))
            ->assertDontSee(sprintf('OS #%06d', $alheia->id));

        $this->get(route('cliente.os.show', $alheia->id))
            ->assertNotFound();

        $this->get(route('cliente.os.show', $propria->id))
            ->assertOk()
            ->assertSee('R$ 230,55');
    }

    public function test_veiculo_compartilhado_nao_expoe_os_alheia(): void
    {
        [$usuarioA, $clienteA] = $this->cliente();
        [, $clienteB] = $this->cliente();

        $veiculo = $this->veiculo($clienteA, 'ABC1D23');
        $veiculo->clientes()->attach($clienteB->id);

        $osA = $this->ordem($clienteA, $veiculo);
        $osB = $this->ordem($clienteB, $veiculo);

        $this->actingAs($usuarioA)
            ->get(route('cliente.os.index', [
                'veiculo' => $veiculo->id,
            ]))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $osA->id))
            ->assertDontSee(sprintf('OS #%06d', $osB->id));

        $this->get(route('cliente.os.show', $osB->id))
            ->assertNotFound();
    }

    public function test_filtro_por_veiculo_respeita_vinculo(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $veiculoA = $this->veiculo($cliente, 'ABC1D23');
        $veiculoB = $this->veiculo($outro, 'DEF4G56');

        $os = $this->ordem($cliente, $veiculoA);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index', [
                'veiculo' => $veiculoA->id,
            ]))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $os->id))
            ->assertSee('ABC1D23')
            ->assertDontSee('DEF4G56');

        $this->get(route('cliente.os.index', [
            'veiculo' => $veiculoB->id,
        ]))
            ->assertOk()
            ->assertSee('Nenhuma ordem de serviço encontrada')
            ->assertDontSee(sprintf('OS #%06d', $os->id));
    }

    public function test_veiculo_inconsistente_nao_expoe_placa(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $veiculoAlheio = $this->veiculo($outro, 'DEF4G56');
        $os = $this->ordem($cliente, $veiculoAlheio);

        $this->actingAs($usuario)
            ->get(route('cliente.os.show', $os->id))
            ->assertOk()
            ->assertSee('Não informado')
            ->assertDontSee('DEF4G56')
            ->assertDontSee('MODELO PORTAL OS');

        $this->get(route('cliente.os.index'))
            ->assertOk()
            ->assertDontSee('DEF4G56');
    }

    public function test_status_e_etapa_sao_mostrados(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $veiculo = $this->veiculo($cliente, 'ABC1D23');

        $os = $this->ordem($cliente, $veiculo);

        $this->actingAs($usuario)
            ->get(route('cliente.os.show', $os->id))
            ->assertOk()
            ->assertSee('Diagnóstico')
            ->assertSee('MECANICA PORTAL')
            ->assertSee('Aberta')
            ->assertSee('ABC1D23');
    }

    public function test_filtro_de_status_e_historico(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $veiculo = $this->veiculo($cliente, 'ABC1D23');

        $aberta = $this->ordem($cliente, $veiculo);

        $finalizada = $this->ordem($cliente, $veiculo, [
            'status' => 'finalizada',
            'data_fechamento' => now(),
        ]);

        $cancelada = $this->ordem($cliente, $veiculo, [
            'status' => 'cancelada',
            'data_fechamento' => now(),
        ]);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index', [
                'status' => 'finalizada',
            ]))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $finalizada->id))
            ->assertDontSee(sprintf('OS #%06d', $aberta->id))
            ->assertDontSee(sprintf('OS #%06d', $cancelada->id));

        $this->get(route('cliente.os.index'))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $aberta->id))
            ->assertSee(sprintf('OS #%06d', $finalizada->id))
            ->assertSee(sprintf('OS #%06d', $cancelada->id));
    }

    public function test_informacoes_internas_nao_sao_expostas(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $veiculo = $this->veiculo($cliente, 'ABC1D23');

        $os = $this->ordem($cliente, $veiculo);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index'))
            ->assertOk()
            ->assertDontSee('SEGREDO_INTERNO_OS')
            ->assertDontSee('Financeiro dos Sócios');

        $this->get(route('cliente.os.show', $os->id))
            ->assertOk()
            ->assertDontSee('SEGREDO_INTERNO_OS')
            ->assertDontSee('Movimentações de Estoque');
    }

    public function test_usuario_sem_cliente_nao_visualiza_os(): void
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        [, $outro] = $this->cliente();
        $veiculo = $this->veiculo($outro, 'DEF4G56');
        $os = $this->ordem($outro, $veiculo);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index'))
            ->assertOk()
            ->assertSee('Nenhuma ordem de serviço encontrada');

        $this->get(route('cliente.os.show', $os->id))
            ->assertNotFound();
    }

    public function test_paginacao_limita_doze_os(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $veiculo = $this->veiculo($cliente, 'ABC1D23');

        $ids = [];

        for ($i = 0; $i < 13; $i++) {
            $ids[] = $this->ordem($cliente, $veiculo)->id;
        }

        $this->actingAs($usuario)
            ->get(route('cliente.os.index'))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $ids[12]))
            ->assertDontSee(sprintf('OS #%06d', $ids[0]));

        $this->get(route('cliente.os.index', [
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSee(sprintf('OS #%06d', $ids[0]))
            ->assertDontSee(sprintf('OS #%06d', $ids[12]));
    }

    public function test_filtros_invalidos_sao_rejeitados(): void
    {
        [$usuario] = $this->cliente();

        $this->actingAs($usuario)
            ->get(route('cliente.os.index', [
                'veiculo' => 'invalido',
                'status' => 'qualquer',
            ]))
            ->assertSessionHasErrors([
                'veiculo',
                'status',
            ]);
    }

    public function test_cliente_bloqueado_nao_acessa_os(): void
    {
        [$usuario] = $this->cliente();

        $usuario->update(['status' => false]);

        $this->actingAs($usuario)
            ->get(route('cliente.os.index'))
            ->assertRedirect(route('login'));
    }

    public function test_rotas_admin_continuam_protegidas(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $veiculo = $this->veiculo($cliente, 'ABC1D23');
        $os = $this->ordem($cliente, $veiculo);

        $this->actingAs($usuario)
            ->get(route('ordemservicos.index'))
            ->assertForbidden();

        $this->get(route('ordemservicos.edit', $os->id))
            ->assertForbidden();

        $this->post(route('ordemservicos.finalizar', $os->id))
            ->assertForbidden();

        $this->post(route('ordemservicos.cancelar', $os->id))
            ->assertForbidden();
    }
}
