<?php

namespace Tests\Feature\Etapas;

use App\Actions\Notas\AlterarEtapaNota;
use App\Actions\Notas\CriarNota;
use App\Models\Etapa;
use App\Models\Nota;
use App\Models\OrdemServico;
use App\Models\NotasItem;
use App\Models\SetorServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DesativarEtapaTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        return User::factory()->create(['perfil' => User::PERFIL_ADMIN]);
    }

    private function formulario(Etapa $etapa, array $alteracoes = []): array
    {
        return array_merge([
            'nome' => $etapa->nome,
            'descricao' => $etapa->descricao,
            'cor' => $etapa->cor,
            'ordem' => $etapa->ordem,
            'aplica_nota' => $etapa->aplica_nota ? '1' : null,
            'aplica_ordem_servico' => $etapa->aplica_ordem_servico ? '1' : null,
            'ativo' => '1',
        ], $alteracoes);
    }

    private function notaEmDiagnostico(string $status = 'Aberto'): Nota
    {
        $nota = app(CriarNota::class)->execute([
            'cliente_id' => null,
            'veiculo_cliente_id' => null,
            'km' => null,
            'km_proxima_troca_oleo' => null,
            'itens' => [],
        ]);
        app(AlterarEtapaNota::class)->execute(
            $nota,
            Etapa::where('slug', 'diagnostico')->firstOrFail()
        );
        $nota->refresh();
        if ($status !== 'Aberto') {
            $nota->update(['status' => $status]);
        }
        return $nota;
    }

    private function ordemEmDiagnostico(): OrdemServico
    {
        $pessoaId = DB::table('pessoas')->insertGetId([
            'nome' => 'Cliente teste', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $clienteId = DB::table('clientes')->insertGetId([
            'pessoa_id' => $pessoaId, 'pontos' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $montadoraId = DB::table('montadoras')->insertGetId([
            'nome' => 'Montadora', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $veiculoId = DB::table('veiculos')->insertGetId([
            'nome' => 'Modelo teste', 'montadora_id' => $montadoraId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $veiculoClienteId = DB::table('veiculos_clientes')->insertGetId([
            'cliente_id' => $clienteId, 'veiculo_id' => $veiculoId,
            'placa' => 'ABC1D23', 'ano' => 2024, 'cor' => 'Azul',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('cliente_veiculo_cliente')->insert([
            'cliente_id' => $clienteId, 'veiculo_cliente_id' => $veiculoClienteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $setor = SetorServico::create(['setor' => 'Mecanica', 'nivel' => 1]);
        return DB::transaction(function () use ($clienteId, $veiculoClienteId, $setor): OrdemServico {
            $nota = app(CriarNota::class)->execute([
                'cliente_id' => $clienteId,
                'veiculo_cliente_id' => $veiculoClienteId,
                'itens' => [],
            ]);
            $os = OrdemServico::create([
                'nota_id' => $nota->id,
                'data_abertura' => now(), 'descricao' => 'Inspecao',
                'valor' => 100, 'status' => 'aberta',
                'cliente_id' => $clienteId, 'veiculo_cliente_id' => $veiculoClienteId,
                'setor_servico_id' => $setor->id,
                'etapa_id' => Etapa::where('slug', 'diagnostico')->value('id'),
            ]);
            NotasItem::create([
                'nota_id' => $nota->id,
                'itemable_type' => OrdemServico::class,
                'itemable_id' => $os->id,
                'descricao' => $os->descricao,
                'quantidade' => 1,
                'valor_unitario' => $os->valor,
                'desconto' => 0,
                'valor_total' => $os->valor,
            ]);
            return $os;
        });
    }

    public function test_desativar_transfere_todas_as_notas_e_os_e_preserva_historico(): void
    {
        $admin = $this->administrador();
        $origem = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $destino = Etapa::inicialParaNota();
        $aberta = $this->notaEmDiagnostico();
        $cancelada = $this->notaEmDiagnostico('Cancelado');
        $os = $this->ordemEmDiagnostico();

        $this->actingAs($admin)
            ->put(route('etapas.update', $origem), $this->formulario($origem, ['ativo' => null]))
            ->assertRedirect(route('etapas.index'));

        $this->assertFalse($origem->fresh()->ativo);
        $this->assertSame($destino->id, $aberta->fresh()->etapa_id);
        $this->assertSame($destino->id, $cancelada->fresh()->etapa_id);
        $this->assertSame('Cancelado', $cancelada->fresh()->status);
        $this->assertSame($destino->id, $os->fresh()->etapa_id);
        $this->assertDatabaseHas('nota_etapa_historicos', [
            'nota_id' => $cancelada->id, 'etapa_origem_id' => $origem->id,
            'etapa_destino_id' => $destino->id, 'user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('ordem_servico_etapa_historicos', [
            'ordem_servico_id' => $os->id, 'etapa_origem_id' => $origem->id,
            'etapa_destino_id' => $destino->id, 'user_id' => $admin->id,
        ]);
        $this->assertSame(0, Nota::where('etapa_id', $origem->id)->count());
        $this->assertSame(0, OrdemServico::where('etapa_id', $origem->id)->count());
    }

    public function test_excluir_etapa_inativa_vazia_preserva_nomes_no_historico(): void
    {
        $admin = $this->administrador();
        $origem = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $nota = $this->notaEmDiagnostico();
        $this->actingAs($admin)->put(
            route('etapas.update', $origem),
            $this->formulario($origem, ['ativo' => null])
        )->assertRedirect();

        $this->delete(route('etapas.destroy', $origem))->assertRedirect(route('etapas.index'));
        $this->assertDatabaseMissing('etapas', ['id' => $origem->id]);
        $this->assertDatabaseHas('nota_etapa_historicos', [
            'nota_id' => $nota->id,
            'etapa_origem_id' => null,
            'etapa_origem_nome' => 'Diagnóstico',
        ]);
    }

    public function test_etapa_ativa_ou_estrutural_nao_pode_ser_excluida(): void
    {
        $admin = $this->administrador();
        $etapa = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $inicial = Etapa::inicialParaNota();
        $this->actingAs($admin)->delete(route('etapas.destroy', $etapa))->assertSessionHasErrors('etapa');
        $this->delete(route('etapas.destroy', $inicial))->assertSessionHasErrors('etapa');
        $this->assertDatabaseHas('etapas', ['id' => $etapa->id]);
        $this->assertDatabaseHas('etapas', ['id' => $inicial->id]);
    }

    public function test_retirar_aplicabilidade_da_nota_realoca_sem_desativar_etapa(): void
    {
        $admin = $this->administrador();
        $etapa = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $nota = $this->notaEmDiagnostico();
        $this->actingAs($admin)->put(
            route('etapas.update', $etapa),
            $this->formulario($etapa, ['aplica_nota' => null])
        )->assertRedirect(route('etapas.index'));
        $this->assertTrue($etapa->fresh()->ativo);
        $this->assertFalse($etapa->fresh()->aplica_nota);
        $this->assertSame(Etapa::inicialParaNota()->id, $nota->fresh()->etapa_id);
    }

    public function test_colaborador_nao_pode_desativar_etapa(): void
    {
        $etapa = Etapa::where('slug', 'diagnostico')->firstOrFail();
        $colaborador = User::factory()->create(['perfil' => User::PERFIL_COLABORADOR]);
        $this->actingAs($colaborador)->put(
            route('etapas.update', $etapa),
            $this->formulario($etapa, ['ativo' => null])
        )->assertForbidden();
        $this->assertTrue($etapa->fresh()->ativo);
    }
}
