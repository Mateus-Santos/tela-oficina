<?php

namespace Tests\Feature\PortalCliente;

use App\Models\Cliente;
use App\Models\Nota;
use App\Models\User;
use App\Models\VeiculosCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotasClienteTest extends TestCase
{
    use RefreshDatabase;

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

    private function nota(
        Cliente $cliente,
        ?VeiculosCliente $veiculo = null,
        array $outros = []
    ): Nota {
        return Nota::create(array_merge([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculo?->id,
            'tipo' => 'Venda',
            'status' => 'Aberto',
            'subtotal' => '25.50',
            'desconto' => '0.00',
            'total' => '25.50',
            'observacoes' => 'DADO_INTERNO_CONFIDENCIAL',
        ], $outros));
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
            'nome' => 'MODELO PORTAL TESTE',
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

    public function test_visitante_e_bloqueado(): void
    {
        $this->get(route('cliente.notas.index'))
            ->assertRedirect(route('erro-autenticacao'));

        $this->get(route('cliente.notas.show', 1))
            ->assertRedirect(route('erro-autenticacao'));
    }

    public function test_admin_e_colaborador_nao_acessam_portal_de_notas(): void
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
                ->get(route('cliente.notas.index'))
                ->assertForbidden();

            $this->get(route('cliente.notas.show', 1))
                ->assertForbidden();
        }
    }

    public function test_cliente_visualiza_apenas_suas_notas(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $propria = $this->nota($cliente);
        $alheia = $this->nota($outro);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $propria->id))
            ->assertDontSee(sprintf('Nota #%06d', $alheia->id));

        $this->get(route('cliente.notas.show', $alheia->id))
            ->assertNotFound();

        $this->get(route('cliente.notas.show', $propria->id))
            ->assertOk()
            ->assertSee('R$ 25,50')
            ->assertDontSee('DADO_INTERNO_CONFIDENCIAL');
    }

    public function test_veiculo_compartilhado_nao_expoe_nota_alheia(): void
    {
        [$usuarioA, $clienteA] = $this->cliente();
        [, $clienteB] = $this->cliente();

        $veiculo = $this->veiculo($clienteA, 'ABC1D23');
        $veiculo->clientes()->attach($clienteB->id);

        $notaA = $this->nota($clienteA, $veiculo);
        $notaB = $this->nota($clienteB, $veiculo);

        $this->actingAs($usuarioA)
            ->get(route('cliente.notas.index', [
                'veiculo' => $veiculo->id,
            ]))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $notaA->id))
            ->assertDontSee(sprintf('Nota #%06d', $notaB->id));

        $this->get(route('cliente.notas.show', $notaB->id))
            ->assertNotFound();
    }

    public function test_filtro_aceita_apenas_veiculo_vinculado(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $veiculoA = $this->veiculo($cliente, 'ABC1D23');
        $veiculoB = $this->veiculo($outro, 'DEF4G56');

        $notaA = $this->nota($cliente, $veiculoA);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index', [
                'veiculo' => $veiculoA->id,
            ]))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $notaA->id))
            ->assertSee('ABC1D23')
            ->assertDontSee('DEF4G56');

        $this->get(route('cliente.notas.index', [
            'veiculo' => $veiculoB->id,
        ]))
            ->assertOk()
            ->assertSee('Nenhuma nota encontrada')
            ->assertDontSee(sprintf('Nota #%06d', $notaA->id));
    }

    public function test_veiculo_inconsistente_nao_tem_dados_expostos(): void
    {
        [$usuario, $cliente] = $this->cliente();
        [, $outro] = $this->cliente();

        $veiculoAlheio = $this->veiculo($outro, 'DEF4G56');
        $nota = $this->nota($cliente, $veiculoAlheio);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.show', $nota->id))
            ->assertOk()
            ->assertSee('Não informado')
            ->assertDontSee('DEF4G56')
            ->assertDontSee('MODELO PORTAL TESTE');

        $this->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertDontSee('DEF4G56');
    }

    public function test_historico_inclui_notas_finalizadas_e_canceladas(): void
    {
        [$usuario, $cliente] = $this->cliente();

        $finalizada = $this->nota($cliente, null, [
            'status' => 'Finalizado',
        ]);

        $cancelada = $this->nota($cliente, null, [
            'status' => 'Cancelado',
        ]);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $finalizada->id))
            ->assertSee(sprintf('Nota #%06d', $cancelada->id))
            ->assertSee('Finalizado')
            ->assertSee('Cancelado');
    }

    public function test_usuario_sem_cliente_nao_enxerga_notas(): void
    {
        $usuario = User::factory()->create([
            'perfil' => User::PERFIL_CLIENTE,
            'status' => true,
        ]);

        [, $outro] = $this->cliente();
        $nota = $this->nota($outro);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee('Nenhuma nota encontrada');

        $this->get(route('cliente.notas.show', $nota->id))
            ->assertNotFound();
    }

    public function test_paginacao_e_estavel(): void
    {
        [$usuario, $cliente] = $this->cliente();

        $ids = [];

        for ($i = 0; $i < 13; $i++) {
            $ids[] = $this->nota($cliente)->id;
        }

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index'))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $ids[12]))
            ->assertDontSee(sprintf('Nota #%06d', $ids[0]));

        $this->get(route('cliente.notas.index', ['page' => 2]))
            ->assertOk()
            ->assertSee(sprintf('Nota #%06d', $ids[0]))
            ->assertDontSee(sprintf('Nota #%06d', $ids[12]));
    }

    public function test_filtro_invalido_e_rejeitado(): void
    {
        [$usuario] = $this->cliente();

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index', [
                'veiculo' => 'invalido',
            ]))
            ->assertSessionHasErrors('veiculo');
    }

    public function test_cliente_bloqueado_nao_acessa_notas(): void
    {
        [$usuario] = $this->cliente();

        $usuario->update(['status' => false]);

        $this->actingAs($usuario)
            ->get(route('cliente.notas.index'))
            ->assertRedirect(route('login'));
    }

    public function test_rotas_administrativas_continuam_bloqueadas(): void
    {
        [$usuario, $cliente] = $this->cliente();
        $nota = $this->nota($cliente);

        $this->actingAs($usuario)
            ->get(route('notas.index'))
            ->assertForbidden();

        $this->get(route('notas.show', $nota->id))
            ->assertForbidden();

        $this->get(route('notas.pdf', $nota->id))
            ->assertForbidden();
    }
}
