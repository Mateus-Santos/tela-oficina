<?php

namespace Tests\Feature\Notas;

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\AtualizarNota;
use App\Actions\Notas\CriarNota;
use App\Models\Cliente;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\Pessoa;
use App\Models\SetorServico;
use App\Models\User;
use App\Models\VeiculosCliente;
use App\Services\Nota\CriarOsComNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FeuxIntegracaoFuncionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Os testes FEUX existentes validam explicitamente o recurso habilitado.
        config()->set('feux_notas.central_vue3', true);
    }

    private function admin(): User
    {
        return User::factory()->create(['perfil' => User::PERFIL_ADMIN, 'status' => true]);
    }

    private function notaEm(Etapa $origem): Nota
    {
        $nota = app(CriarNota::class)->execute([
            'cliente_id' => null, 'veiculo_cliente_id' => null, 'itens' => [],
        ]);
        app(AlterarEtapaNota::class)->execute($nota, $origem);
        return $nota->fresh();
    }

    private function osComNota(int $userId): array
    {
        $cliente = Cliente::create([
            'pessoa_id' => Pessoa::create(['nome' => 'Cliente teste Gate E'])->id,
            'pontos' => 0,
        ]);
        $agora = now();
        $montadora = DB::table('montadoras')->insertGetId([
            'nome' => 'Gate E montadora', 'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $veiculo = DB::table('veiculos')->insertGetId([
            'nome' => 'Gate E modelo', 'montadora_id' => $montadora,
            'created_at' => $agora, 'updated_at' => $agora,
        ]);
        $veiculoCliente = VeiculosCliente::create([
            'cliente_id' => $cliente->id,
            'veiculo_id' => $veiculo,
            'placa' => 'GAT1E23', 'ano' => 2024, 'cor' => 'Branco',
        ]);
        $veiculoCliente->clientes()->attach($cliente->id);
        $setor = SetorServico::create(['setor' => 'Gate E servicos', 'nivel' => 1]);
        $resultado = app(CriarOsComNota::class)->execute([
            'cliente_id' => $cliente->id,
            'veiculo_cliente_id' => $veiculoCliente->id,
            'setor_servico_id' => $setor->id,
            'descricao' => 'Servico de integracao', 'valor' => 125.50,
        ], $userId);
        return [$resultado['nota'], $resultado['os']];
    }

    public function test_cliente_nao_acessa_api_central_de_notas(): void
    {
        $user = User::factory()->create(['perfil' => User::PERFIL_CLIENTE, 'status' => true]);
        $this->actingAs($user)
            ->getJson(route('notas.vue.dados'))->assertForbidden();
    }

    public function test_lote_move_notas_abertas_e_registra_historico(): void
    {
        $user = $this->admin();
        $origem = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $destino = Etapa::where('slug', 'em-manutencao')->firstOrFail();
        $a = $this->notaEm($origem);
        $b = $this->notaEm($origem);

        $previa = $this->actingAs($user)->getJson(route('notas.vue.etapas.lote.preview', [
            'origem_etapa_id' => $origem->id,
            'destino_etapa_id' => $destino->id,
        ]))->assertOk()->assertJsonPath('contagem', 2);

        $this->postJson(route('notas.vue.etapas.lote.executar'), [
            'origem_etapa_id' => $origem->id,
            'destino_etapa_id' => $destino->id,
            'assinatura' => $previa->json('assinatura'),
            'confirmacao' => 'MOVER_ETAPA_LOTE',
        ])->assertOk()->assertJsonPath('movidas', 2);

        foreach ([$a, $b] as $nota) {
            $this->assertSame($destino->id, $nota->fresh()->etapa_id);
            $this->assertDatabaseHas('nota_etapa_historicos', [
                'nota_id' => $nota->id, 'etapa_origem_id' => $origem->id,
                'etapa_destino_id' => $destino->id,
            ]);
        }
    }

    public function test_lote_recusa_previa_desatualizada_sem_movimentos_parciais(): void
    {
        $user = $this->admin();
        $origem = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $destino = Etapa::where('slug', 'em-manutencao')->firstOrFail();
        $a = $this->notaEm($origem);
        $previa = $this->actingAs($user)->getJson(route('notas.vue.etapas.lote.preview', [
            'origem_etapa_id' => $origem->id, 'destino_etapa_id' => $destino->id,
        ]))->assertOk();
        $b = $this->notaEm($origem);

        $this->postJson(route('notas.vue.etapas.lote.executar'), [
            'origem_etapa_id' => $origem->id,
            'destino_etapa_id' => $destino->id,
            'assinatura' => $previa->json('assinatura'),
            'confirmacao' => 'MOVER_ETAPA_LOTE',
        ])->assertStatus(422);
        $this->assertSame($origem->id, $a->fresh()->etapa_id);
        $this->assertSame($origem->id, $b->fresh()->etapa_id);
    }

    public function test_lote_rejeita_etapa_final(): void
    {
        $user = $this->admin();
        $origem = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $final = Etapa::query()->paraNota()->ativas()->finais()->firstOrFail();
        $this->actingAs($user)->getJson(route('notas.vue.etapas.lote.preview', [
            'origem_etapa_id' => $origem->id,
            'destino_etapa_id' => $final->id,
        ]))->assertStatus(422);
    }

    public function test_nota_com_os_canonica_nao_e_excluida_sem_item_comercial(): void
    {
        $user = $this->admin();
        [$nota, $os] = $this->osComNota($user->id);
        DB::table('notas_itens')->where('nota_id', $nota->id)
            ->where('itemable_type', OrdemServico::class)->delete();

        $this->actingAs($user)->deleteJson(route('notas.vue.excluir', $nota))
            ->assertStatus(422);
        $this->assertDatabaseHas('notas', ['id' => $nota->id]);
        $this->assertDatabaseHas('ordem_servicos', ['id' => $os->id, 'nota_id' => $nota->id]);
    }

    public function test_edicao_nao_remove_item_comercial_de_os_existente(): void
    {
        $user = $this->admin();
        [$nota, $os] = $this->osComNota($user->id);
        try {
            app(AtualizarNota::class)->execute($nota, [
                'cliente_id' => $os->cliente_id,
                'veiculo_cliente_id' => $os->veiculo_cliente_id,
                'itens' => [],
            ]);
            $this->fail('Nao pode remover o item comercial de O.S. persistida.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors()['itens'] ?? []);
        }
        $this->assertDatabaseHas('ordem_servicos', ['id' => $os->id, 'nota_id' => $nota->id]);
        $this->assertDatabaseHas('notas_itens', [
            'nota_id' => $nota->id,
            'itemable_type' => OrdemServico::class,
            'itemable_id' => $os->id,
        ]);
    }

    public function test_detalhe_nao_oferece_exclusao_com_os_canonica_sem_item(): void
    {
        $user = $this->admin();
        [$nota, $os] = $this->osComNota($user->id);
        DB::table('notas_itens')->where('nota_id', $nota->id)
            ->where('itemable_type', OrdemServico::class)->delete();

        $this->actingAs($user)
            ->getJson(route('notas.vue.detalhe', $nota))
            ->assertOk()
            ->assertJsonPath('data.pode_excluir', false);
        $this->assertDatabaseHas('ordem_servicos', [
            'id' => $os->id, 'nota_id' => $nota->id,
        ]);
    }

    public function test_busca_legada_nao_oferece_os_ja_vinculada_pela_fk(): void
    {
        $user = $this->admin();
        [$nota, $os] = $this->osComNota($user->id);
        DB::table('notas_itens')->where('nota_id', $nota->id)
            ->where('itemable_type', OrdemServico::class)->delete();

        $this->actingAs($user)
            ->getJson(route('api.notas-itens.buscar', [
                'tipo' => 'os',
                'q' => (string) $os->id,
                'cliente_id' => $os->cliente_id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $os->id)
            ->assertJsonPath('data.0.disponivel', false)
            ->assertJsonPath('data.0.nota.id', $nota->id);
    }
}
